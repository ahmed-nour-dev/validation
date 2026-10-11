<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::DECIMAL)]
final class DecimalRule implements RuleInterface
{
    private int $min;
    private ?int $max;

    public function __construct(int $min, ?int $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_numeric($value)) {
            return ['rule' => 'decimal', 'parameters' => ['min' => (string)$this->min, 'max' => (string)$this->max]];
        }

        $value = (string) $value;
        $parts = explode('.', $value);

        if (count($parts) !== 2) {
            return ['rule' => 'decimal', 'parameters' => ['min' => (string)$this->min, 'max' => (string)$this->max]];
        }

        $decimalLength = strlen($parts[1]);

        if ($this->max === null) {
            if ($decimalLength !== $this->min) {
                return ['rule' => 'decimal', 'parameters' => ['min' => (string)$this->min, 'max' => (string)$this->max]];
            }
        } else {
            if ($decimalLength < $this->min || $decimalLength > $this->max) {
                return ['rule' => 'decimal', 'parameters' => ['min' => (string)$this->min, 'max' => (string)$this->max]];
            }
        }

        return null;
    }
}
