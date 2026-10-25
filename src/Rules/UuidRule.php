<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::UUID)]
final class UuidRule implements RuleInterface, NativeCompilableInterface
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value)) {
            return ['rule' => 'uuid'];
        }

        if (!preg_match(self::UUID_PATTERN, $value)) {
            return ['rule' => 'uuid'];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $pattern = NativeCompilationContext::literal(self::UUID_PATTERN);

        return $context->emitError("(!is_string({$v}) || !preg_match({$pattern}, {$v}))", 'uuid');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
