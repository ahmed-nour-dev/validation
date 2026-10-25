<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Compilation\NativeSizeCompiler;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::BETWEEN)]
final class BetweenRule implements RuleInterface, NumericAwareInterface, NativeCompilableInterface
{
    private int|float $min;
    private int|float $max;
    private bool $isNumeric = false;

    public function __construct(int|float $min, int|float $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function setNumeric(bool $numeric): void
    {
        $this->isNumeric = $numeric;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $size = $this->getSize($value);
        
        // Determine type
        $type = 'numeric';
        if (is_array($value)) {
            $type = 'array';
        } elseif (is_string($value)) {
            $type = 'string';
        }

        if ($size < $this->min || $size > $this->max) {
            return ['rule' => 'between', 'params' => ['type' => $type, 'min' => $this->min, 'max' => $this->max]];
        }

        return null;
    }

    private function getSize(mixed $value): int|float
    {
        if (is_numeric($value)) {
            // If explicitly numeric or naturally numeric
            if ($this->isNumeric || is_int($value) || is_float($value)) {
                return (float) $value;
            }
        }

        if (is_string($value)) {
            if ($this->isNumeric && is_numeric($value)) {
                return (float) $value;
            }
            return mb_strlen($value);
        }

        if (is_array($value)) {
            return count($value);
        }

        return 0;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $min = NativeCompilationContext::literal($this->min);
        $max = NativeCompilationContext::literal($this->max);
        $inner = $context->withIndent($context->indent . '    ');

        return NativeSizeCompiler::emitSize($context, $this->isNumeric)
            . "{$context->indent}if (\$size < {$min} || \$size > {$max}) {\n"
            . $inner->errorStatement('between', "['type' => \$type_tag, 'min' => {$min}, 'max' => {$max}]")
            . "{$context->indent}}\n";
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
