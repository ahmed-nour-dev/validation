<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

/**
 * A database validator that can answer many `exists` / `unique` checks with one query.
 *
 * Used by lookup batching (see SchemaValidator::enableBatchLookups()): the values a rule will
 * see across a chunk of rows are collected and resolved with a single `whereIn`, instead of
 * one query per row.
 */
interface BatchDatabaseValidatorInterface extends DatabaseValidatorInterface
{
    /**
     * Return those of $values that match at least one row, as the database stores them.
     *
     * Rows whose $idColumn equals $ignoreId are not counted (`unique:...,ignore`).
     * Return null when this lookup cannot be batched; callers then fall back to per-row queries.
     *
     * @param list<string|int> $values
     * @param array<string, mixed> $extraConstraints
     * @return list<string|int|float>|null
     */
    public function matchingValues(string $table, string $column, array $values, mixed $ignoreId = null, string $idColumn = 'id', array $extraConstraints = [], ?string $connection = null): ?array;
}
