<?php

declare(strict_types=1);

namespace Vi\Validation\Execution;

use Vi\Validation\Rules\BatchDatabaseValidatorInterface;
use Vi\Validation\Rules\DatabaseLookup;
use Vi\Validation\Rules\DatabaseValidatorInterface;
use Vi\Validation\Rules\ExistsRule;
use Vi\Validation\Rules\UniqueRule;

/**
 * Answers `exists` / `unique` checks from one query per chunk instead of one per row.
 *
 * It decorates a BatchDatabaseValidatorInterface. prime() collects the values the schema's
 * database rules will see across a chunk of rows and resolves them with a `whereIn` per
 * distinct lookup; until clear() is called, checks for primed values are answered from that
 * result. Anything that can't be answered with certainty (unprimed values, closure
 * constraints, values the database might match under its collation but not byte-for-byte)
 * is delegated to the wrapped validator, so results always equal the per-row ones.
 *
 * Holds per-chunk state: always pair prime() with clear(), and don't share an instance
 * between concurrent validations.
 */
final class LookupBatcher implements DatabaseValidatorInterface
{
    /** Values per whereIn; stays under SQLite's historical 999-variable limit. */
    private const QUERY_CHUNK = 500;

    /** @var list<DatabaseLookup> */
    private array $lookups = [];

    /**
     * @var array<string, array{found: array<string, true>, fuzzy: array<string, true>, leftover: bool}>
     */
    private array $primed = [];

    private int $queries = 0;

    public function __construct(private readonly BatchDatabaseValidatorInterface $inner)
    {
    }

    public function inner(): BatchDatabaseValidatorInterface
    {
        return $this->inner;
    }

    /**
     * Number of batched queries issued so far.
     */
    public function queryCount(): int
    {
        return $this->queries;
    }

    /**
     * Remember which lookups the schema performs; returns false if it has none to batch.
     */
    public function plan(CompiledSchema $schema): bool
    {
        $this->lookups = [];
        $seen = [];

        foreach ($schema->getFields() as $field) {
            foreach ($field->getRules() as $rule) {
                if ($rule instanceof ExistsRule || $rule instanceof UniqueRule) {
                    $lookup = $rule->lookup();
                    $key = $lookup->key();
                    if ($key !== null && !isset($seen[$key])) {
                        $seen[$key] = true;
                        $this->lookups[] = $lookup;
                    }
                }
            }
        }

        return $this->lookups !== [];
    }

    /**
     * Resolve every planned lookup for these rows with one query per (lookup, 500 values).
     *
     * @param array<array-key, array<string, mixed>> $rows
     */
    public function prime(CompiledSchema $schema, array $rows): void
    {
        $this->primed = [];
        $wanted = [];

        foreach ($schema->getFields() as $field) {
            foreach ($field->getRules() as $rule) {
                if (!($rule instanceof ExistsRule || $rule instanceof UniqueRule)) {
                    continue;
                }

                $key = $rule->lookup()->key();
                if ($key === null) {
                    continue;
                }

                foreach ($rows as $row) {
                    foreach ($this->valuesOf($field, $row) as $value) {
                        $wanted[$key][(string) $value] = $value;
                    }
                }
            }
        }

        foreach ($this->lookups as $lookup) {
            $key = $lookup->key();
            if ($key === null || !isset($wanted[$key])) {
                continue;
            }

            $found = [];
            foreach (array_chunk(array_values($wanted[$key]), self::QUERY_CHUNK) as $values) {
                $matches = $this->inner->matchingValues(
                    $lookup->table,
                    $lookup->column,
                    $values,
                    $lookup->ignoreId,
                    $lookup->idColumn,
                    $lookup->extraConstraints,
                    $lookup->connection
                );
                $this->queries++;

                if ($matches === null) {
                    continue 2; // not batchable: leave unprimed, rules query per row
                }

                foreach ($matches as $match) {
                    $found[(string) $match] = true;
                }
            }

            $fuzzy = [];
            foreach ($found as $match => $_) {
                $fuzzy[self::fuzzyKey((string) $match)] = true;
            }

            // A database value no input equals byte-for-byte can only have matched through the
            // collation (case, accents, trailing spaces); it may belong to any unmatched input.
            $leftover = array_diff_key($found, $wanted[$key]) !== [];

            $this->primed[$key] = ['found' => $found, 'fuzzy' => $fuzzy, 'leftover' => $leftover];
        }
    }

    public function clear(): void
    {
        $this->primed = [];
    }

    public function exists(string $table, string $column, mixed $value, array $extraConstraints = [], ?string $connection = null): bool
    {
        $answer = $this->lookupPrimed($table, $column, $value, null, 'id', $extraConstraints, $connection);

        return $answer ?? $this->inner->exists($table, $column, $value, $extraConstraints, $connection);
    }

    public function unique(string $table, string $column, mixed $value, mixed $ignoreId = null, string $idColumn = 'id', array $extraConstraints = [], ?string $connection = null): bool
    {
        $answer = $this->lookupPrimed($table, $column, $value, $ignoreId, $idColumn, $extraConstraints, $connection);

        return $answer !== null ? !$answer : $this->inner->unique($table, $column, $value, $ignoreId, $idColumn, $extraConstraints, $connection);
    }

    /**
     * Whether the value matches a row, or null when it must be asked of the database directly.
     *
     * @param array<string, mixed> $extraConstraints
     */
    private function lookupPrimed(string $table, string $column, mixed $value, mixed $ignoreId, string $idColumn, array $extraConstraints, ?string $connection): ?bool
    {
        if ($this->primed === [] || !self::batchable($value)) {
            return null;
        }

        $key = DatabaseLookup::keyFor($table, $column, $ignoreId, $idColumn, $extraConstraints, $connection);
        $set = $key !== null ? ($this->primed[$key] ?? null) : null;
        if ($set === null) {
            return null;
        }

        $string = (string) $value;
        if (isset($set['found'][$string])) {
            return true;
        }

        // Unmatched: certain only if the database could not have matched it some other way.
        if ($set['leftover'] || isset($set['fuzzy'][self::fuzzyKey($string)]) || preg_match('/[^\x00-\x7F]/', $string) === 1) {
            return null;
        }

        return false;
    }

    private static function batchable(mixed $value): bool
    {
        return is_string($value) || is_int($value);
    }

    private static function fuzzyKey(string $value): string
    {
        return is_numeric($value) ? 'n' . (float) $value : 's' . strtolower(rtrim($value));
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string|int>
     */
    private function valuesOf(CompiledField $field, array $row): array
    {
        $values = [];

        if ($field->isWildcard()) {
            foreach (DataHelper::expand($row, $field->getName()) as [$name]) {
                $values[] = DataHelper::get($row, $name);
            }
        } else {
            $values[] = $field->getValue($row);
        }

        return array_values(array_filter($values, self::batchable(...)));
    }
}
