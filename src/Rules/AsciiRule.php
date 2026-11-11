<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::ASCII)]
final class AsciiRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (is_string($value) && mb_check_encoding($value, 'ASCII')) {
            return null;
        }

        return ['rule' => 'ascii'];
    }
}
