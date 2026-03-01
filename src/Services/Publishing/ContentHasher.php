<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Publishing;

/**
 * ContentHasher
 *
 * Produces a stable SHA-256 hash for composed content by:
 *  - canonicalizing arrays/objects (sorting associative keys recursively)
 *  - encoding to compact JSON with stable representation
 *
 * This ensures the same logical content (regardless of key ordering) yields the same hash.
 */
final class ContentHasher
{
    /**
     * Compute a SHA-256 hex hash for the given content.
     */
    public function hash(array $content): string
    {
        $canonical = $this->canonicalize($content);
        $json = json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode canonical JSON.');
        }

        return hash('sha256', $json);
    }

    /**
     * Recursively canonicalize a value:
     *  - associative arrays are sorted by key
     *  - indexed arrays preserve order
     *  - scalar values are returned as-is
     */
    private function canonicalize(mixed $value): mixed
    {
        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                ksort($value);
                foreach ($value as $k => &$v) {
                    $v = $this->canonicalize($v);
                }
                unset($v);
                return $value;
            }

            // Indexed array: canonicalize each element and ensure sequential keys
            foreach ($value as $i => &$v) {
                $v = $this->canonicalize($v);
            }
            unset($v);
            return array_values($value);
        }

        // Convert objects implementing __toString to string
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $value;
    }

    /**
     * Determine if an array is associative.
     */
    private function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
