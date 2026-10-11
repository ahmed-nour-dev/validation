<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::DIGITS_BETWEEN)]
final class DigitsBetweenRule implements RuleInterface
{
    private int $min;
    private int $max;

    public function __construct(int $min, int $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        // Non-scalars (arrays, objects) are simply invalid; casting them throws
        // ("Array to string conversion"), where Laravel 13 returns a validation failure.
        if (!is_scalar($value)) {
            return [
                'rule' => 'digits_between',
                'params' => ['min' => $this->min, 'max' => $this->max],
            ];
        }

        $stringValue = (string) $value;

        // Check if the value contains only digits
        if (!ctype_digit($stringValue)) {
            return [
                'rule' => 'digits_between',
                'params' => ['min' => $this->min, 'max' => $this->max],
            ];
        }

        $length = strlen($stringValue);

        // Check if the length is within range
        if ($length < $this->min || $length > $this->max) {
            return [
                'rule' => 'digits_between',
                'params' => ['min' => $this->min, 'max' => $this->max],
            ];
        }

        return null;
    }
}
