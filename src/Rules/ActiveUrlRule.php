<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::ACTIVE_URL)]
final class ActiveUrlRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'active_url'];
        }

        if (!checkdnsrr($value, 'A') && !checkdnsrr($value, 'AAAA') && !checkdnsrr($value, 'CNAME')) {
            return ['rule' => 'active_url'];
        }

        return null;
    }
}
