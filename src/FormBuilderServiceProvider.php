<?php

declare(strict_types=1);

namespace Packages\FormBuilder;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Packages\FormBuilder\Contracts\SchemaValidatorInterface;
use Packages\FormBuilder\Services\Validation\OpisJsonSchemaValidator;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Policies\FormPolicy;
use Packages\FormBuilder\Console\Commands\PublishFormsCommand;
use Packages\FormBuilder\Console\Commands\LintFormsCommand;
use Packages\FormBuilder\Console\Commands\SeedSampleFormsCommand;
use Packages\FormBuilder\Contracts\FormsManagerInterface;
use Packages\FormBuilder\Services\FormsManager;
use Illuminate\Foundation\AliasLoader;
use Packages\FormBuilder\Facades\Former;

class FormBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        // Merge package config (config file can be added later).
        // Be defensive: only merge if the config file actually exists to allow
        // auto-discovery to work before the package config is published.
        $path = __DIR__ . '/../config/forms.php';
        if (method_exists($this, 'mergeConfigFrom') && file_exists($path)) {
            $this->mergeConfigFrom($path, 'forms');
        }

        // Merge Spatie Laravel Data config defaults so DTO transformations work
        // in host applications that haven't published the vendor config.
        $spatiePath = __DIR__ . '/../config/data.php';
        if (method_exists($this, 'mergeConfigFrom') && file_exists($spatiePath)) {
            $this->mergeConfigFrom($spatiePath, 'data');
        }

        // Ensure carsdotcom package has sensible defaults so validator works without vendor:publish.
        if ($this->app['config']->get('json-schema') === null) {
            $this->app['config']->set('json-schema', [
                'base_url' => 'file://localhost/',
                'local_base_prefix' => base_path('app/Schemas/'),
                'local_base_prefix_tests' => base_path('tests/Schemas/'),
                'storage_disk_name' => 'schemas',
            ]);
        }

        // Register Opis validator and formatter as singletons to be injected into our OpisJsonSchemaValidator.
        $this->app->singleton(Validator::class, function ($app) {
            return new Validator();
        });

        $this->app->singleton(ErrorFormatter::class, function ($app) {
            return new ErrorFormatter();
        });

        // Bind the schema validator interface to the concrete implementation.
        // This allows swapping implementations in host applications or tests.
        $this->app->bind(SchemaValidatorInterface::class, function ($app) {
            return new OpisJsonSchemaValidator(
                $app->make(Validator::class),
                $app->make(ErrorFormatter::class)
            );
        });

        // Bind the FormsManager interface to its concrete implementation and expose a container entry.
        $this->app->bind(
            FormsManagerInterface::class,
            FormsManager::class
        );

        $this->app->singleton('forms.manager', fn($app) => $app->make(FormsManagerInterface::class));

        // Optionally register a class alias for the Facade when AliasLoader and the Facade exist.
        if (class_exists(AliasLoader::class) && class_exists(Former::class)) {
            AliasLoader::getInstance()->alias('Forms', Former::class);
        }
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Register middleware alias so hosts can reference "idempotency" by name.
        if (class_exists(\Illuminate\Routing\Router::class)) {
            $router = $this->app->make(Router::class);
            $router->aliasMiddleware('idempotency', \Packages\FormBuilder\Http\Middleware\EnsureIdempotencyKey::class);
        }

        // Register package policies (Visibility / owner scoping).
        if (class_exists(\Illuminate\Contracts\Auth\Access\Gate::class)) {
            Gate::policy(Form::class, FormPolicy::class);
        }

        // Make package migrations available for automatic loading by Laravel.
        $migrationsPath = __DIR__ . '/../database/migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Publish configuration and other resources when running in console.
        if ($this->app->runningInConsole()) {
            // Register package artisan commands for hosts and local developers.
            $this->commands([
                PublishFormsCommand::class,
                LintFormsCommand::class,
                SeedSampleFormsCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/forms.php' => config_path('forms.php'),
                __DIR__ . '/../config/data.php' => config_path('data.php'),
            ], 'form-builder-config');

            // Allow publishing of migrations so hosts can customize or inspect them.
            $this->publishes([
                $migrationsPath => database_path('migrations/vendor/form-builder'),
            ], 'form-builder-migrations');
        }

        // Load package routes if the routes file exists so the package can be
        // used immediately after installation (without requiring a config
        // publish step).
      $routes = __DIR__ . '/../routes/api.php';
      if (file_exists($routes)) {
        // Wrap package routes in the app's API group so they receive the 'api' middleware/prefix semantics.
        Route::prefix('api')->middleware('api')->group($routes);
      }
    }
}
