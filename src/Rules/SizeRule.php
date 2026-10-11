<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::SIZE)]
final class SizeRule implements RuleInterface, NumericAwareInterface
{
    private int|float $size;
    private bool $isNumeric = false;

    public function __construct(int|float $size)
    {
        $this->size = $size;
    }

    public function setNumeric(bool $numeric): void
    {
        $this->isNumeric = $numeric;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $actualSize = $this->getSize($value);
        
        // Determine type
        $type = 'numeric';
        if (is_array($value)) {
            $type = 'array';
        } elseif (is_string($value)) {
            $type = 'string';
        }

        // Use loose comparison to handle int/float differences
        if ((float) $actualSize != (float) $this->size) {
            return ['rule' => 'size', 'params' => ['type' => $type, 'size' => $this->size]];
        }

        return null;
    }

    private function getSize(mixed $value): int|float
    {
        if (is_numeric($value)) {
            // If explicitly numeric or naturally numeric
            if ($this->isNumeric || is_int($value) || is_float($value)) {
                return (float) $value;
            }
        }

        if (is_string($value)) {
            if ($this->isNumeric && is_numeric($value)) {
                return (float) $value;
            }
            return mb_strlen($value);
        }

        if (is_array($value)) {
            return count($value);
        }

        return 0;
    }
}
