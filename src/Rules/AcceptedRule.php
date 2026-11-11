<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::ACCEPTED)]
final class AcceptedRule implements RuleInterface
{
    private const ACCEPTABLE = ['yes', 'on', '1', 1, true, 'true'];

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if ($value === null) {
            return ['rule' => 'accepted'];
        }

        if (!in_array($value, self::ACCEPTABLE, true)) {
            return ['rule' => 'accepted'];
        }

        return null;
    }
}
