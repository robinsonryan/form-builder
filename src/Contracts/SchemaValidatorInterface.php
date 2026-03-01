<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Contracts;

use Packages\FormBuilder\Data\ValidationResultData;

interface SchemaValidatorInterface
{
    /**
     * Validate the provided data against the given JSON Schema.
     *
     * Returns a ValidationResultData wrapper. When valid, ->isValid() === true and ->errors is null.
     *
     * @param array<mixed>|object $data
     * @param array<mixed>|object $schema
     */
    public function validate(array|object $data, array|object $schema): ValidationResultData;
}
