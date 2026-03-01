<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Publishing;

use InvalidArgumentException;

/**
 * Composes fragments by-value with:
 *  - parameter substitution (only enum, title, default, description)
 *  - property rename map (updates properties and required entries)
 *  - insertion at a JSON Pointer within a target schema
 */
final class FragmentComposer
{
    private const ALLOWED_PARAM_KEYS = ['enum', 'title', 'default', 'description'];

    /**
     * Insert a fragment into a base schema at the given JSON Pointer.
     *
     * @param array  $baseSchema   The schema into which fragment will be inserted (will not be modified)
     * @param string $jsonPointer  JSON Pointer (RFC 6901) where to insert the fragment (e.g. "/properties")
     * @param array  $fragment     Fragment schema (expects ['properties' => [...]] and optional 'required')
     * @param array  $params       Parameter overrides keyed by property name. Each value may include only enum/title/default/description
     * @param array  $renameMap    Map of originalPropertyName => newPropertyName
     *
     * @return array The new schema with the fragment inserted
     *
     * @throws InvalidArgumentException on invalid params or rename targets
     */
    public function insertInto(array $baseSchema, string $jsonPointer, array $fragment, array $params = [], array $renameMap = []): array
    {
        $fragment = $this->applyParams($fragment, $params);
        $fragment = $this->applyRenameMap($fragment, $renameMap);

        // Work on a copy of base schema
        $result = $baseSchema;

        // Resolve pointer and insert
        $targetNode =& $this->resolvePointerReference($result, $jsonPointer);

        // If the target node appears to be a properties container (associative array of properties),
        // and fragment provides 'properties', merge them in.
        if ($this->isPropertiesContainer($targetNode) && isset($fragment['properties'])) {
            foreach ($fragment['properties'] as $propName => $propSchema) {
                $targetNode[$propName] = $propSchema;
            }
        } else {
            // Otherwise, replace the value at the pointer with the fragment
            $targetNode = $fragment;
        }

        // Merge required entries (rename map already applied)
        if (isset($fragment['required']) && is_array($fragment['required'])) {
            if (!isset($result['required']) || !is_array($result['required'])) {
                $result['required'] = [];
            }

            foreach ($fragment['required'] as $req) {
                if (!in_array($req, $result['required'], true)) {
                    $result['required'][] = $req;
                }
            }
        }

        return $result;
    }

    /**
     * Apply param overrides to the fragment's properties.
     *
     * @param array $fragment
     * @param array $params
     *
     * @return array
     */
    private function applyParams(array $fragment, array $params): array
    {
        if (empty($params)) {
            return $fragment;
        }

        if (!isset($fragment['properties']) || !is_array($fragment['properties'])) {
            throw new InvalidArgumentException('Fragment does not contain properties to apply params to.');
        }

        foreach ($params as $propName => $overrides) {
            if (!array_key_exists($propName, $fragment['properties'])) {
                throw new InvalidArgumentException(sprintf('Parameter target "%s" not found in fragment properties.', $propName));
            }

            if (!is_array($overrides)) {
                throw new InvalidArgumentException(sprintf('Overrides for "%s" must be an array.', $propName));
            }

            foreach ($overrides as $key => $value) {
                if (!in_array($key, self::ALLOWED_PARAM_KEYS, true)) {
                    throw new InvalidArgumentException(sprintf('Parameter key "%s" is not allowed. Allowed keys: %s', $key, implode(',', self::ALLOWED_PARAM_KEYS)));
                }

                // Enforce enum shape
                if ($key === 'enum' && !is_array($value)) {
                    throw new InvalidArgumentException('Enum override must be an array.');
                }

                $fragment['properties'][$propName][$key] = $value;
            }
        }

        return $fragment;
    }

    /**
     * Apply rename map to fragment properties and required arrays.
     *
     * @param array $fragment
     * @param array $renameMap
     *
     * @return array
     */
    private function applyRenameMap(array $fragment, array $renameMap): array
    {
        if (empty($renameMap)) {
            return $fragment;
        }

        if (!isset($fragment['properties']) || !is_array($fragment['properties'])) {
            throw new InvalidArgumentException('Fragment does not contain properties to rename.');
        }

        // Validate renameMap targets exist
        foreach ($renameMap as $from => $to) {
            if (!array_key_exists($from, $fragment['properties'])) {
                throw new InvalidArgumentException(sprintf('Rename source "%s" not found in fragment properties.', $from));
            }
            if (!is_string($to) || $to === '') {
                throw new InvalidArgumentException(sprintf('Rename target for "%s" must be a non-empty string.', $from));
            }
        }

        // Perform renames on properties
        foreach ($renameMap as $from => $to) {
            // Skip no-op renames
            if ($from === $to) {
                continue;
            }
            // If target name already exists, overwrite it
            $fragment['properties'][$to] = $fragment['properties'][$from];
            unset($fragment['properties'][$from]);
        }

        // Update required entries if present
        if (isset($fragment['required']) && is_array($fragment['required'])) {
            $newRequired = [];
            foreach ($fragment['required'] as $req) {
                $newRequired[] = $renameMap[$req] ?? $req;
            }
            $fragment['required'] = array_values(array_unique($newRequired));
        }

        return $fragment;
    }

    /**
     * Resolve a JSON Pointer into the document and return a reference.
     * If pointer points to a path that doesn't exist, intermediate objects/arrays are created.
     *
     * @param array  $document
     * @param string $pointer
     *
     * @return mixed
     */
    private function &resolvePointerReference(array &$document, string $pointer)
    {
        // RFC 6901: an empty string points to the whole document
        if ($pointer === '') {
            return $document;
        }

        if (substr($pointer, 0, 1) !== '/') {
            throw new InvalidArgumentException('JSON Pointer must be empty or start with "/".');
        }

        $parts = explode('/', ltrim($pointer, '/'));
        $node =& $document;

        foreach ($parts as $part) {
            $part = $this->unescapePointer($part);

            // If node is not an array, convert to array to allow insertion
            if (!is_array($node)) {
                $node = [];
            }

            // Create intermediate if missing
            if (!array_key_exists($part, $node) || !isset($node[$part])) {
                $node[$part] = [];
            }

            $node =& $node[$part];
        }

        return $node;
    }

    /**
     * Unescape a JSON Pointer token.
     */
    private function unescapePointer(string $token): string
    {
        return str_replace(['~1', '~0'], ['/', '~'], $token);
    }

    /**
     * Decide if the target node is a properties container (associative array).
     *
     * @param mixed $node
     *
     * @return bool
     */
    private function isPropertiesContainer(mixed $node): bool
    {
        return is_array($node);
    }
}
