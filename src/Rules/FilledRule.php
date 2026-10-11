<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The field under validation must not be empty when it is present.
 */
#[RuleName(RuleId::FILLED)]
final class FilledRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        // A missing key passes; a key that is present with null fails (Laravel: filled => required).
        if ($value === null) {
            return $context->hasValue($field) ? ['rule' => 'filled'] : null;
        }

        // If the field is present but empty, fail validation
        if (is_string($value) && \Vi\Validation\Execution\Emptiness::isBlankString($value)) {
            return ['rule' => 'filled'];
        }

        if (is_array($value) && $value === []) {
            return ['rule' => 'filled'];
        }

        return null;
    }
}
