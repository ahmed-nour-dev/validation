<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::NOT_IN)]
final class NotInRule implements RuleInterface, NativeCompilableInterface
{
    /** @var list<string> */
    private array $values;

    /** @param list<string> $values */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    private bool $arrayContext = false;

    /**
     * Set when the field also has the `array` rule (Laravel checks each element then).
     */
    public function setArrayContext(bool $arrayContext): void
    {
        $this->arrayContext = $arrayContext;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (is_array($value)) {
            // Laravel defines not_in as exactly !in: with an `array` rule on the field it fails
            // only when *every* element is in the list (and none is itself an array); without
            // one an array never matches `in`, so `not_in` passes.
            if ($this->arrayContext && $value !== []) {
                foreach ($value as $item) {
                    if (!is_scalar($item) || !in_array((string) $item, $this->values, true)) {
                        return null;
                    }
                }

                return ['rule' => 'not_in'];
            }

            return null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return null;
        }

        if (in_array((string) $value, $this->values, true)) {
            return ['rule' => 'not_in'];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $list = NativeCompilationContext::literal($this->values);
        $i = $context->indent;
        $scalar = "(is_scalar({$v}) || {$v} instanceof \\Stringable)";

        if (!$this->arrayContext) {
            // An array never matches `in`, so `not_in` passes it.
            return $context->emitError("({$scalar} && in_array((string) {$v}, {$list}, true))", 'not_in');
        }

        // With an `array` rule on the field a non-empty array fails only when every element is in the list.
        return "{$i}\$notin_bad = false;\n"
            . "{$i}if (is_array({$v})) {\n"
            . "{$i}    if ({$v} !== []) {\n"
            . "{$i}        \$notin_bad = true;\n"
            . "{$i}        foreach ({$v} as \$notin_item) {\n"
            . "{$i}            if (!is_scalar(\$notin_item) || !in_array((string) \$notin_item, {$list}, true)) { \$notin_bad = false; break; }\n"
            . "{$i}        }\n"
            . "{$i}    }\n"
            . "{$i}} elseif ({$scalar}) {\n"
            . "{$i}    \$notin_bad = in_array((string) {$v}, {$list}, true);\n"
            . "{$i}}\n"
            . $context->emitError('$notin_bad', 'not_in');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
