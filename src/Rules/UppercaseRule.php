<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::UPPERCASE)]
final class UppercaseRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'uppercase'];
        }

        if (mb_strtoupper($value, 'UTF-8') !== $value) {
            return ['rule' => 'uppercase'];
        }

        return null;
    }
}
