<?php

declare(strict_types=1);

namespace Vi\Validation\Execution;

/**
 * Helper class for data access.
 */
final class DataHelper
{
    /**
     * Get a value from an array using dot notation.
     *
     * @param array<string, mixed> $data
     */
    public static function get(array $data, string $key, mixed $default = null): mixed
    {
        if (strpos($key, '.') === false) {
            return $data[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * Whether a dot-notation path exists in the array (the leaf may be null).
     *
     * @param array<array-key, mixed> $data
     */
    public static function has(array $data, string $key): bool
    {
        if (strpos($key, '.') === false) {
            return array_key_exists($key, $data);
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return false;
            }

            $data = $data[$segment];
        }

        return true;
    }

    /**
     * Expand a wildcard path ('items.*.sku') against the data in a single depth-first
     * traversal, like Laravel's wildcard expansion (ValidationData::initializeAndGatherData()):
     * every '*' matches each key of the array at that position, and a missing trailing
     * segment under an existing element still expands (so `required` can fail on it). A
     * wildcard over a missing, non-array or empty node expands to nothing.
     *
     * @param array<array-key, mixed> $data
     * @return list<array{0: string, 1: list<string>}> concrete path => the keys '*' matched, in order
     */
    public static function expand(array $data, string $pattern): array
    {
        $out = [];
        self::expandInto($data, explode('.', $pattern), 0, '', [], $out);

        return $out;
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<string> $segments
     * @param list<string> $keys
     * @param list<array{0: string, 1: list<string>}> $out
     */
    private static function expandInto(array $node, array $segments, int $i, string $path, array $keys, array &$out): void
    {
        $last = count($segments) - 1;

        for (; $i <= $last; $i++) {
            $segment = $segments[$i];

            if ($segment === '*') {
                foreach ($node as $key => $child) {
                    $childPath = $path === '' ? (string) $key : $path . '.' . $key;
                    $childKeys = [...$keys, (string) $key];

                    if ($i === $last) {
                        $out[] = [$childPath, $childKeys];
                    } else {
                        self::expandInto(is_array($child) ? $child : [], $segments, $i + 1, $childPath, $childKeys, $out);
                    }
                }

                return;
            }

            $path = $path === '' ? $segment : $path . '.' . $segment;

            if ($i === $last) {
                $out[] = [$path, $keys];

                return;
            }

            if (!array_key_exists($segment, $node) || !is_array($node[$segment])) {
                // The rest of an explicit tail still expands (Laravel fills it with null so
                // `required` can fail); a further wildcard has nothing left to match.
                if (!in_array('*', array_slice($segments, $i + 1), true)) {
                    $out[] = [$path . '.' . implode('.', array_slice($segments, $i + 1)), $keys];
                }

                return;
            }

            $node = $node[$segment];
        }
    }
}
