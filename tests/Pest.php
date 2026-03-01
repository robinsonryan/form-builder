<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Config\Repository as ConfigRepository;

/**
 * Minimal Pest bootstrap for package-level tests.
 *
 * Ensures a basic container instance and a 'config' binding exist so code
 * that relies on app()->make('config') (or Spatie packages) can run in tests.
 */

$container = new Container();
$container->instance('config', new ConfigRepository([]));
Container::setInstance($container);
