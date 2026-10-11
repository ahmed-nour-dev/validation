<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::MAC_ADDRESS)]
final class MacAddressRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'mac_address'];
        }

        if (!filter_var($value, FILTER_VALIDATE_MAC)) {
            return ['rule' => 'mac_address'];
        }

        return null;
    }
}
