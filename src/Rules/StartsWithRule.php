<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::STARTS_WITH)]
final class StartsWithRule implements RuleInterface, NativeCompilableInterface
{
    /** @var list<string> */
    private array $values;

    /**
     * @param list<string> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return [
                'rule' => 'starts_with',
                'params' => ['values' => implode(', ', $this->values)],
            ];
        }

        foreach ($this->values as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return null;
            }
        }

        return [
            'rule' => 'starts_with',
            'params' => ['values' => implode(', ', $this->values)],
        ];
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $checks = array_map(
            static fn (mixed $affix): string => 'str_starts_with(' . $v . '_s, ' . NativeCompilationContext::literal($affix) . ')',
            $this->values
        );
        $matches = $checks === [] ? 'false' : implode(' || ', $checks);
        $i = $context->indent;

        return "{$i}{$v}_s = is_numeric({$v}) ? (string) {$v} : {$v};\n"
            . $context->emitError("(!is_string({$v}_s) || !({$matches}))", 'starts_with', "['values' => " . NativeCompilationContext::literal(implode(', ', $this->values)) . ']');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
