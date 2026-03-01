<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Publishing;

use InvalidArgumentException;

/**
 * SlotComposer
 *
 * Composes tenant extensions into a base schema by applying fragments only into
 * slots explicitly declared by the base schema.
 *
 * Expected base schema shape:
 * [
 *   ...,
 *   'slots' => [
 *       'slot_name' => '/json/pointer/into/schema',
 *       ...
 *   ],
 * ]
 *
 * Expected extension shape (per extension):
 * [
 *   'slot' => 'slot_name',
 *   'fragment' => [...],        // fragment schema (properties/required etc)
 *   'params' => [...],         // optional param overrides passed to FragmentComposer
 *   'rename_map' => [...],     // optional rename map passed to FragmentComposer
 * ]
 *
 * Behavior:
 * - Throws InvalidArgumentException if the base schema does not declare slots but extensions are provided.
 * - Throws InvalidArgumentException if any extension references a slot not declared in the base schema.
 * - Applies each extension by delegating to FragmentComposer::insertInto using the pointer declared for the slot.
 */
final class SlotComposer
{
    private FragmentComposer $fragmentComposer;

    public function __construct(FragmentComposer $fragmentComposer)
    {
        $this->fragmentComposer = $fragmentComposer;
    }

    /**
     * Compose the given extensions into the base schema using declared slots.
     *
     * @param array $baseSchema
     * @param array $extensions
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function compose(array $baseSchema, array $extensions): array
    {
        $result = $baseSchema;

        if (empty($extensions)) {
            return $result;
        }

        if (!isset($baseSchema['slots']) || !is_array($baseSchema['slots'])) {
            throw new InvalidArgumentException('Base schema does not declare any slots.');
        }

        foreach ($extensions as $extension) {
            if (!isset($extension['slot']) || !is_string($extension['slot'])) {
                throw new InvalidArgumentException('Extension missing "slot" name.');
            }

            $slotName = $extension['slot'];

            if (!array_key_exists($slotName, $baseSchema['slots'])) {
                throw new InvalidArgumentException(sprintf('Slot "%s" is not declared in base schema.', $slotName));
            }

            $pointer = $baseSchema['slots'][$slotName];
            if (!is_string($pointer) || $pointer === '') {
                throw new InvalidArgumentException(sprintf('Slot "%s" has an invalid pointer.', $slotName));
            }

            if (!isset($extension['fragment']) || !is_array($extension['fragment'])) {
                throw new InvalidArgumentException(sprintf('Extension for slot "%s" must include a fragment array.', $slotName));
            }

            $params = $extension['params'] ?? [];
            $renameMap = $extension['rename_map'] ?? [];

            $result = $this->fragmentComposer->insertInto($result, $pointer, $extension['fragment'], $params, $renameMap);
        }

        return $result;
    }
}
