<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation;

use InvalidArgumentException;

/**
 * StepSubschemaBuilder
 *
 * Builds a per-step subschema from a full JSON Schema and a ui_step_maps structure.
 *
 * ui_step_maps format expected:
 * [
 *   'step_id' => [
 *       '/properties/name',
 *       '/properties/address/properties/street',
 *       '/properties/nestedArray/items/properties/inner',
 *       'simplePropertyName', // also accepted as shorthand for '/properties/simplePropertyName'
 *   ],
 *   ...
 * ]
 *
 * The builder will:
 *  - include only the referenced properties (and parent objects required to contain them)
 *  - preserve required flags when the original schema marks the parent/property as required
 *  - for nested objects, include only the referenced child properties under their parent
 */
final class StepSubschemaBuilder
{
    /**
     * Build a subschema for the given step id.
     *
     * @param array  $schema     Full JSON Schema (expects top-level 'properties' and optional 'required')
     * @param array  $uiStepMaps Mapping of step id => array of json pointers or simple property names
     * @param string $stepId
     *
     * @return array Subschema (top-level type object with 'properties' and optional 'required')
     *
     * @throws InvalidArgumentException
     */
    public function build(array $schema, array $uiStepMaps, string $stepId): array
    {
        if (!array_key_exists($stepId, $uiStepMaps)) {
            throw new InvalidArgumentException(sprintf('Step id "%s" not found in ui_step_maps.', $stepId));
        }

        $entries = $uiStepMaps[$stepId];
        if (!is_array($entries)) {
            throw new InvalidArgumentException('ui_step_maps step entry must be an array of pointers or property names.');
        }

        $result = [
            'type' => $schema['type'] ?? 'object',
            'properties' => [],
        ];

        $topRequired = $schema['required'] ?? [];
        if (is_array($topRequired) && $topRequired !== []) {
            $result['required'] = [];
        }

        foreach ($entries as $entry) {
            // normalize shorthand property name to pointer
            if (is_string($entry) && substr($entry, 0, 1) !== '/') {
                $pointer = '/properties/' . $entry;
            } else {
                $pointer = (string) $entry;
            }

            $this->ensurePointerValid($pointer);

            // Attempt to resolve pointer to the property schema and the path tokens
            $resolved = $this->resolvePointer($schema, $pointer);

            if ($resolved === null) {
                throw new InvalidArgumentException(sprintf('Pointer "%s" does not resolve to a property in the schema.', $pointer));
            }

            [$tokens, $propSchema] = $resolved;

            // Insert into result schema building parents as necessary
            $this->insertIntoResult($result, $schema, $tokens, $propSchema);
        }

        // If top-level required was populated to empty, remove it
        if (isset($result['required']) && $result['required'] === []) {
            unset($result['required']);
        }

        return $result;
    }

    /**
     * Ensure pointer looks like a JSON Pointer or property shorthand.
     */
    private function ensurePointerValid(string $pointer): void
    {
        if ($pointer === '') {
            throw new InvalidArgumentException('Empty pointer is not supported.');
        }

        if ($pointer[0] !== '/') {
            // shorthand should have been normalized earlier; but keep check
            throw new InvalidArgumentException(sprintf('Invalid pointer "%s".', $pointer));
        }
    }

    /**
     * Resolve a JSON Pointer against the schema and return [propertyTokenPathArray, propertySchema]
     *
     * Only supports pointers that navigate through '/properties' and optional '/items' nodes.
     * Returns null if cannot resolve to a property definition.
     *
     * Examples:
     *  '/properties/name' => [['name'], <schema for name>]
     *  '/properties/address/properties/street' => [['address','street'], <schema for street>]
     *  '/properties/arr/items/properties/inner' => [['arr','items','inner'], <schema for inner>]
     *
     * @return array|null
     */
    private function resolvePointer(array $schema, string $pointer): ?array
    {
        $parts = explode('/', ltrim($pointer, '/'));
        $node = $schema;
        $tokens = [];

        $i = 0;
        while ($i < count($parts)) {
            $part = $this->unescapePointer($parts[$i]);

            if ($part === 'properties') {
                $i++;
                if (!isset($parts[$i])) {
                    return null;
                }
                $propName = $this->unescapePointer($parts[$i]);
                if (!isset($node['properties']) || !is_array($node['properties']) || !array_key_exists($propName, $node['properties'])) {
                    return null;
                }
                $node = $node['properties'][$propName];
                $tokens[] = $propName;
                $i++;
                continue;
            }

            if ($part === 'items') {
                // move into items schema
                if (!isset($node['items']) || !is_array($node['items'])) {
                    return null;
                }
                // represent 'items' as a navigation token in tokens to allow nested arrays
                $tokens[] = 'items';
                $node = $node['items'];
                $i++;
                continue;
            }

            // unsupported token encountered (e.g., additionalProperties etc.)
            return null;
        }

        // $node now points to the property schema resolved
        return [$tokens, $node];
    }

    /**
     * Insert the resolved property into the result schema, creating intermediate parent objects/arrays
     * that mirror the original schema shape but only include selected children and required entries.
     *
     * @param array $result Reference to result schema being built
     * @param array $original Original full schema (to consult required arrays and object indicators)
     * @param array $tokens Path tokens (e.g., ['address','street'] or ['arr','items','inner'])
     * @param array $propSchema Schema of the resolved property to insert
     */
    private function insertIntoResult(array &$result, array $original, array $tokens, array $propSchema): void
    {
        // Navigate original and result in parallel
        $origNode = $original;
        $resNode =& $result;

        // Ensure top-level properties container exists
        if (!isset($resNode['properties'])) {
            $resNode['properties'] = [];
        }

        $depth = count($tokens);
        for ($i = 0; $i < $depth; $i++) {
            $token = $tokens[$i];

            if ($token === 'items') {
                // For arrays, we ensure 'items' exists in result under current property
                // The previous token (i-1) should correspond to an array property name in result and origNode
                // Move origNode into its 'items'
                if (!isset($origNode['items']) || !is_array($origNode['items'])) {
                    // original didn't have items, cannot proceed
                    throw new InvalidArgumentException('Original schema does not contain expected items for array navigation.');
                }

                // Ensure resNode has 'items'
                if (!isset($resNode['items']) || !is_array($resNode['items'])) {
                    $resNode['items'] = ['type' => $origNode['items']['type'] ?? 'object', 'properties' => []];
                }

                // Move pointers
                $origNode = $origNode['items'];
                $resNode =& $resNode['items'];
                continue;
            }

            // token is property name
            // If this is the last token, insert the property schema
            $isLast = ($i === $depth - 1);

            // Ensure origNode has properties
            if (!isset($origNode['properties']) || !is_array($origNode['properties']) || !array_key_exists($token, $origNode['properties'])) {
                throw new InvalidArgumentException(sprintf('Property "%s" not found in original schema while inserting.', $token));
            }

            $origProp = $origNode['properties'][$token];

            // Ensure resNode has properties container
            if (!isset($resNode['properties']) || !is_array($resNode['properties'])) {
                $resNode['properties'] = [];
            }

            if ($isLast) {
                // Insert the property schema (copy)
                $resNode['properties'][$token] = $origProp;

                // If original had required on this level, and token listed, preserve it on this level in result
                if (isset($origNode['required']) && is_array($origNode['required']) && in_array($token, $origNode['required'], true)) {
                    if (!isset($resNode['required']) || !is_array($resNode['required'])) {
                        $resNode['required'] = [];
                    }
                    if (!in_array($token, $resNode['required'], true)) {
                        $resNode['required'][] = $token;
                    }
                }

                // Also, if this insertion happens at top-level, propagate top-level required from original
                if ($resNode === $result && isset($original['required']) && is_array($original['required'])) {
                    foreach ($original['required'] as $req) {
                        if (!isset($result['required'])) {
                            $result['required'] = [];
                        }
                        if (!in_array($req, $result['required'], true) && array_key_exists($req, ($result['properties'] ?? []))) {
                            $result['required'][] = $req;
                        }
                    }
                }

                // If parent orig node had required for the parent property (e.g., address required), ensure top-level required contains parent
                // The above loop already covers top-level. For nested parents, ensure their required arrays are updated when children get added.
                return;
            }

            // Not last token: descend into nested object
            // Ensure property exists in result as an object mirroring original shape (but with empty properties)
            if (!isset($resNode['properties'][$token]) || !is_array($resNode['properties'][$token])) {
                // Start with original property's type if available
                $resNode['properties'][$token] = [
                    'type' => $origProp['type'] ?? 'object',
                    'properties' => [],
                ];
            } else {
                // Ensure properties key exists
                if (!isset($resNode['properties'][$token]['properties']) || !is_array($resNode['properties'][$token]['properties'])) {
                    $resNode['properties'][$token]['properties'] = [];
                }
            }

            // If original required marks this child property as required, preserve it on this level
            if (isset($origNode['required']) && is_array($origNode['required']) && in_array($token, $origNode['required'], true)) {
                if (!isset($resNode['required']) || !is_array($resNode['required'])) {
                    $resNode['required'] = [];
                }
                if (!in_array($token, $resNode['required'], true)) {
                    $resNode['required'][] = $token;
                }
            }

            // Descend both origNode and resNode
            $origNode = $origProp;
            $resNode =& $resNode['properties'][$token];
        }
    }

    /**
     * Unescape a JSON Pointer token.
     */
    private function unescapePointer(string $token): string
    {
        return str_replace(['~1', '~0'], ['/', '~'], $token);
    }
}
