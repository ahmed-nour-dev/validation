<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::REGEX)]
final class RegexRule implements RuleInterface, NativeCompilableInterface
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value) && !is_numeric($value)) {
            return ['rule' => 'regex'];
        }

        if (!preg_match($this->pattern, (string) $value)) {
            return ['rule' => 'regex'];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $pattern = NativeCompilationContext::literal($this->pattern);

        return $context->emitError("((!is_string({$v}) && !is_numeric({$v})) || !preg_match({$pattern}, (string) {$v}))", 'regex');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
