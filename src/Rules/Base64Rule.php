<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The value must be a canonical Base64 string (Laravel `base64`): it must decode strictly and
 * re-encode to exactly the same string, so missing padding and stray characters are rejected.
 */
#[RuleName(RuleId::BASE64)]
final class Base64Rule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (is_string($value) && $value !== '') {
            $decoded = base64_decode($value, true);

            if ($decoded !== false && base64_encode($decoded) === $value) {
                return null;
            }
        }

        return ['rule' => 'base64'];
    }
}
