<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

/**
 * The identity of an `exists` / `unique` lookup: two checks with the same key can be answered
 * by the same query result.
 */
final class DatabaseLookup
{
    /**
     * @param array<string, mixed> $extraConstraints
     */
    public function __construct(
        public readonly string $table,
        public readonly string $column,
        public readonly mixed $ignoreId = null,
        public readonly string $idColumn = 'id',
        public readonly array $extraConstraints = [],
        public readonly ?string $connection = null
    ) {
    }

    /**
     * @param array<string, mixed> $extraConstraints
     */
    public static function keyFor(string $table, string $column, mixed $ignoreId, string $idColumn, array $extraConstraints, ?string $connection): ?string
    {
        // Closures (Rule::unique()->where(fn ...)) and other non-scalar constraints can't be
        // compared or merged, so such lookups stay per-row.
        if (($ignoreId !== null && !is_scalar($ignoreId)) || !self::scalarOnly($extraConstraints)) {
            return null;
        }

        return serialize([$table, $column, $ignoreId, $idColumn, $extraConstraints, $connection]);
    }

    public function key(): ?string
    {
        return self::keyFor($this->table, $this->column, $this->ignoreId, $this->idColumn, $this->extraConstraints, $this->connection);
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private static function scalarOnly(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && !is_scalar($value)) {
                return false;
            }
        }

        return true;
    }
}
