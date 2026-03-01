<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Contracts;

interface PackageContract
{
    /**
     * Handle the given payload and return a result.
     *
     * @param array $payload
     * @return mixed
     */
    public function handle(array $payload): mixed;
}
