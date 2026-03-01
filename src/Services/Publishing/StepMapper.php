<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Publishing;

use Packages\FormBuilder\Data\StepDescriptorData;

/**
 * Derives a flat list of step descriptors from a JSON-Forms Categorization UI schema.
 *
 * This is intentionally minimal: it understands the common pattern:
 *  - root type "Categorization" with "elements" that are "Category" objects
 *  - each Category has a "label" (used as the step title) and "elements" (controls)
 *
 * The returned StepDescriptorData items contain:
 *  - id: derived from label or generated if missing
 *  - title: category label
 *  - index: zero-based order
 *  - ui_schema: the Category node (as provided)
 *  - schema: left as null (schema derivation is out of scope for this utility)
 */
final class StepMapper
{
    /**
     * Derive step descriptors from a UI schema.
     *
     * @param array<mixed> $uiSchema
     * @return StepDescriptorData[]
     */
    public function derive(array $uiSchema): array
    {
        $steps = [];

        if (($uiSchema['type'] ?? '') !== 'Categorization') {
            return $steps;
        }

        $elements = $uiSchema['elements'] ?? [];
        if (!is_array($elements)) {
            return $steps;
        }

        $index = 0;
        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            if (($element['type'] ?? '') !== 'Category') {
                continue;
            }

            $label = $element['label'] ?? null;
            $id = $this->deriveId($label, $index);
            $title = $label ?? ('Step ' . ($index + 1));

            $steps[] = new StepDescriptorData(
                id: (string) $id,
                title: (string) $title,
                index: $index,
                ui_schema: $element,
                schema: null
            );

            $index++;
        }

        return $steps;
    }

    private function deriveId(?string $label, int $index): string
    {
        if ($label === null || $label === '') {
            return 'step-' . $index;
        }

        // simple slug: lowercase, replace non-alnum with dashes, trim duplicates
        $slug = mb_strtolower((string) $label);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            return 'step-' . $index;
        }

        return $slug;
    }
}
