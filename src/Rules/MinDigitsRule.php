<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The integer value must have at least :min digits (Laravel `min_digits`).
 *
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::MIN_DIGITS)]
final class MinDigitsRule implements RuleInterface
{
    public function __construct(private readonly int $min)
    {
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_scalar($value) || preg_match('/[^0-9]/', (string) $value) === 1 || strlen((string) $value) < $this->min) {
            return ['rule' => 'min_digits', 'params' => ['min' => $this->min]];
        }

        return null;
    }
}
