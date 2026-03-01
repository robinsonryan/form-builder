<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Packages\FormBuilder\FormBuilderServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
abstract class TestCase extends Orchestra
{
  /**
   * Register package service providers.
   *
   * @param \Illuminate\Foundation\Application $app
   * @return array
   */
  protected function getPackageProviders($app): array
  {
    return [
      FormBuilderServiceProvider::class,
    ];
  }

  /**
   * Set up environment for tests. Prefer the DB connection provided by the environment
   * (ddev/Postgres). Fall back to sqlite in-memory if DB_CONNECTION is not set.
   *
   * @param \Illuminate\Foundation\Application $app
   * @return void
   */
  protected function getEnvironmentSetUp($app): void
  {
    $default = env('DB_CONNECTION', 'pgsql');
    $app['config']->set('database.default', $default);

    if ($default === 'sqlite') {
      $app['config']->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
      ]);
    } else {
      // Respect environment-provided PG connection settings (ddev typically provides these).
      $app['config']->set('database.connections.pgsql', [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '32805'),
        'database' => env('DB_DATABASE', 'db'),
        'username' => env('DB_USERNAME', 'db'),
        'password' => env('DB_PASSWORD', 'db'),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => env('DB_SCHEMA', 'public'),
        'sslmode' => env('DB_SSLMODE', 'prefer'),
      ]);
    }
  }

  /**
   * Run package migrations before each test.
   *
   * @return void
   */
  protected function setUp(): void
  {
    parent::setUp();

    EloquentFactory::guessFactoryNamesUsing(static function (string $modelName) {
      return 'Packages\\FormBuilder\\Database\\Factories\\' . class_basename($modelName) . 'Factory';
    });
    // Load and run the package migrations that live in the package database/migrations directory.
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

    // Ensure migrations are executed.
    $this->artisan('migrate:fresh', ['--force' => true])->run();

    // Register package artisan commands so artisan() helper can find them in package tests.
    $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->registerCommand(new \Packages\FormBuilder\Console\Commands\LintFormsCommand());
    $kernel->registerCommand(new \Packages\FormBuilder\Console\Commands\PublishFormsCommand());
    $kernel->registerCommand(new \Packages\FormBuilder\Console\Commands\SeedSampleFormsCommand());
  }
}
