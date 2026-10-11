<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::MULTIPLE_OF)]
final class MultipleOfRule implements RuleInterface
{
    public function __construct(private int|float $factor)
    {
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_numeric($value)) {
            return ['rule' => 'multiple_of', 'parameters' => ['value' => $this->factor]];
        }

        $value = (float) $value;
        $factor = (float) $this->factor;

        if ($factor == 0) {
            return ['rule' => 'multiple_of', 'parameters' => ['value' => $this->factor]];
        }

        if (fmod($value, $factor) !== 0.0) {
            return ['rule' => 'multiple_of', 'parameters' => [0 => $this->factor, 'value' => $this->factor]];
        }

        return null;
    }
}
