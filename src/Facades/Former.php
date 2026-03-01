<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade accessor for the Forms manager.
 *
 * Provides a simple, expressive static entry point for host applications and
 * package code, e.g. \Packages\FormBuilder\Facades\Former::publish(...).
 */
final class Former extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'forms.manager';
    }
}
