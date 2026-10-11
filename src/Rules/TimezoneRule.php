<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use DateTimeZone;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::TIMEZONE)]
final class TimezoneRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'timezone'];
        }

        if (!in_array($value, DateTimeZone::listIdentifiers(), true)) {
            return ['rule' => 'timezone'];
        }

        return null;
    }
}
