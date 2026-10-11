<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::ULID)]
final class UlidRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'ulid'];
        }

        // ULIDs are 26 characters long and use Crockford's Base32 (0-9, A-Z excluding I, L, O, U)
        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value)) {
            return ['rule' => 'ulid'];
        }

        return null;
    }
}
