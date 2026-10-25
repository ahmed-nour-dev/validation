<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::SAME)]
final class SameRule implements RuleInterface, NativeCompilableInterface
{
    private string $otherField;

    public function __construct(string $otherField)
    {
        $this->otherField = $otherField;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $otherValue = $context->getValue($this->otherField);

        if ($value !== $otherValue) {
            return ['rule' => 'same'];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $i = $context->indent;

        return "{$i}{$v}_o = " . NativeCompilationContext::lookup($this->otherField) . ";\n"
            . $context->emitError("{$v} !== {$v}_o", 'same');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
