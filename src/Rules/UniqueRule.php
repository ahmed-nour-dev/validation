<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::UNIQUE)]
final class UniqueRule implements RuleInterface
{
    private string $table;
    private string $column;
    private mixed $ignoreId;
    private string $idColumn;
    private ?string $connection;
    /** @var array<string, mixed> */
    private array $extraConstraints;
    private ?string $ignoreIdField;

    /**
     * @param array<string, mixed> $extraConstraints
     * @param string|null $ignoreIdField Laravel's `[field]` syntax: name of the row field holding the id
     *                                   to ignore, resolved at validation time (overrides $ignoreId)
     */
    public function __construct(string $table, string $column = 'id', mixed $ignoreId = null, string $idColumn = 'id', array $extraConstraints = [], ?string $connection = null, ?string $ignoreIdField = null)
    {
        $this->ignoreIdField = $ignoreIdField;
        $this->table = $table;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
        $this->idColumn = $idColumn;
        $this->extraConstraints = $extraConstraints;
        $this->connection = $connection;
    }

    public function lookup(): DatabaseLookup
    {
        return new DatabaseLookup($this->table, $this->column, $this->ignoreId, $this->idColumn, $this->extraConstraints, $this->connection, $this->ignoreIdField !== null);
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if ($value === null) {
            return null;
        }

        $dbValidator = $context->getDatabaseValidator();

        if ($dbValidator === null) {
            // Fail closed, like Laravel ("Presence verifier has not been set."): silently
            // passing would let duplicate/unknown values through.
            throw new \RuntimeException(
                'The [unique] rule requires a database validator. Call setDatabaseValidator() on the '
                . 'ValidatorEngine (FastValidatorFactory wires Laravel\'s presence verifier automatically).'
            );
        }

        $ignoreId = $this->ignoreIdField !== null
            ? $this->resolveIgnoreId($context->getValue($this->ignoreIdField))
            : $this->ignoreId;

        if (!$dbValidator->unique($this->table, $this->column, $value, $ignoreId, $this->idColumn, $this->extraConstraints, $this->connection)) {
            return ['rule' => 'unique'];
        }

        return null;
    }

    /**
     * Normalise a row value the way Laravel's prepareUniqueId() does for `[field]` ids.
     */
    private function resolveIgnoreId(mixed $id): mixed
    {
        if (!is_scalar($id)) {
            return null;
        }

        if (is_string($id)) {
            if (strtolower($id) === 'null') {
                return null;
            }

            $id = stripslashes($id);
        }

        if (filter_var($id, FILTER_VALIDATE_INT) !== false) {
            return (int) $id;
        }

        return $id;
    }
}
