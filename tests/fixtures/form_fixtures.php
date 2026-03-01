<?php

declare(strict_types=1);

use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;

if (!function_exists('create_test_form_and_version')) {
    /**
     * Create a minimal Form and FormVersion using package factories.
     *
     * @param array $formAttrs
     * @param array $versionAttrs
     * @return array [Form $form, FormVersion $version]
     */
    function create_test_form_and_version(array $formAttrs = [], array $versionAttrs = []): array
    {
        $form = Form::factory()->create($formAttrs);
        $version = FormVersion::factory()->create(array_merge(['form_id' => $form->id], $versionAttrs));

        return [$form, $version];
    }
}
