<?php

declare(strict_types=1);

namespace Vi\Validation\Execution;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Holds batch validation results with support for streaming iteration.
 *
 * @implements IteratorAggregate<int, ValidationResult>
 */
final class BatchValidationResult implements IteratorAggregate, Countable
{
    /** @var list<ValidationResult> */
    private array $results;

    /**
     * @param list<ValidationResult> $results
     *
     * @internal Not part of the public API; see docs/public-api.md.
     */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * @return list<ValidationResult>
     */
    public function all(): array
    {
        return $this->results;
    }

    /**
     * @return Traversable<int, ValidationResult>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    public function count(): int
    {
        return count($this->results);
    }

    /**
     * Check if all results are valid.
     */
    public function allValid(): bool
    {
        foreach ($this->results as $result) {
            if (!$result->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get only failed validation results.
     *
     * @return list<ValidationResult>
     */
    public function failures(): array
    {
        return array_values(array_filter(
            $this->results,
            fn (ValidationResult $result) => !$result->isValid()
        ));
    }

    /**
     * Get count of failed validations.
     */
    public function failureCount(): int
    {
        $count = 0;

        foreach ($this->results as $result) {
            if (!$result->isValid()) {
                $count++;
            }
        }

        return $count;
    }
}
