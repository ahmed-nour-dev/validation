<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::NUMERIC)]
final class NumericRule implements RuleInterface, NativeCompilableInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_numeric($value)) {
            return ['rule' => 'numeric'];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;

        return $context->emitError("!is_numeric({$v})", 'numeric');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
