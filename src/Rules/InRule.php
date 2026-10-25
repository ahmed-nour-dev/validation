<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::IN)]
final class InRule implements RuleInterface, NativeCompilableInterface
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
            // Laravel: with an `array` rule on the field every element must be in the list;
            // otherwise an array never matches.
            if (!$this->arrayContext) {
                return ['rule' => 'in'];
            }
            foreach ($value as $item) {
                if (!is_scalar($item) || !in_array((string) $item, $this->values, true)) {
                    return ['rule' => 'in'];
                }
            }

            return null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return ['rule' => 'in'];
        }

        if (!in_array((string) $value, $this->values, true)) {
            return ['rule' => 'in'];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $list = NativeCompilationContext::literal($this->values);
        $i = $context->indent;
        $scalar = "(is_scalar({$v}) || {$v} instanceof \\Stringable)";
        $notIn = "!in_array((string) {$v}, {$list}, true)";

        if (!$this->arrayContext) {
            return $context->emitError("(!{$scalar} || {$notIn})", 'in');
        }

        // With an `array` rule on the field every element must be in the list.
        return "{$i}if (is_array({$v})) {\n"
            . "{$i}    \$in_ok = true;\n"
            . "{$i}    foreach ({$v} as \$in_item) {\n"
            . "{$i}        if (!is_scalar(\$in_item) || !in_array((string) \$in_item, {$list}, true)) { \$in_ok = false; break; }\n"
            . "{$i}    }\n"
            . "{$i}} else {\n"
            . "{$i}    \$in_ok = {$scalar} && in_array((string) {$v}, {$list}, true);\n"
            . "{$i}}\n"
            . $context->emitError('!$in_ok', 'in');
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
