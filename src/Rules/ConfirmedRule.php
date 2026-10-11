<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::CONFIRMED)]
final class ConfirmedRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $context->getValue($confirmationField);

        if ($value !== $confirmationValue) {
            return ['rule' => 'confirmed'];
        }

        return null;
    }
}
