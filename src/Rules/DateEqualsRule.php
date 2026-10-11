<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::DATE_EQUALS)]
final class DateEqualsRule implements RuleInterface
{
    public function __construct(private string $date)
    {
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'date_equals', 'parameters' => ['date' => $this->date]];
        }

        $timestamp = strtotime($value);
        $compareToTimestamp = strtotime($this->date);

        if ($timestamp === false || $compareToTimestamp === false || $timestamp !== $compareToTimestamp) {
            return ['rule' => 'date_equals', 'parameters' => [0 => $this->date, 'date' => $this->date]];
        }

        return null;
    }
}
