<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::LT)]
final class LessThanRule implements RuleInterface, NumericAwareInterface
{
    private string $otherField;
    private bool $isNumeric = false;

    public function __construct(string $otherField)
    {
        $this->otherField = $otherField;
    }

    public function setNumeric(bool $numeric): void
    {
        $this->isNumeric = $numeric;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $otherValue = $context->getValue($this->otherField);

        if ($otherValue === null) {
            return null;
        }

        // Handle numeric comparison
        if (is_numeric($value) && is_numeric($otherValue)) {
            // Force numeric comparison if context is set or both are naturally numeric
            if ($this->isNumeric || (is_numeric($value) && is_numeric($otherValue))) {
                if ((float) $value >= (float) $otherValue) {
                    return [
                        'rule' => 'lt',
                        'params' => ['value' => $this->otherField],
                    ];
                }
                return null;
            }
        }

        // Handle string comparison (by length)
        if (is_string($value) && is_string($otherValue)) {
            // If explicit numeric context, try to compare as numbers first
            if ($this->isNumeric && is_numeric($value) && is_numeric($otherValue)) {
                 if ((float) $value >= (float) $otherValue) {
                    return [
                        'rule' => 'lt',
                        'params' => ['value' => $this->otherField],
                    ];
                }
                return null;
            }

            if (mb_strlen($value) >= mb_strlen($otherValue)) {
                return [
                    'rule' => 'lt',
                    'params' => ['value' => $this->otherField],
                ];
            }
            return null;
        }

        // Handle array comparison (by count)
        if (is_array($value) && is_array($otherValue)) {
            if (count($value) >= count($otherValue)) {
                return [
                    'rule' => 'lt',
                    'params' => ['value' => $this->otherField],
                ];
            }
            return null;
        }

        // Values of different types are not comparable; Laravel fails them (isSameType()).
        return [
            'rule' => 'lt',
            'params' => ['value' => $this->otherField],
        ];
    }
}
