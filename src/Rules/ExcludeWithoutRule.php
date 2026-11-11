<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::EXCLUDE_WITHOUT)]
final class ExcludeWithoutRule implements RuleInterface
{
    private string $otherField;

    public function __construct(string $otherField)
    {
        $this->otherField = $otherField;
    }

    public function shouldExclude(ValidationContext $context): bool
    {
        return !$context->hasValue($this->otherField);
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        return null;
    }
}
