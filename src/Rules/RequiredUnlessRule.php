<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\Emptiness;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::REQUIRED_UNLESS)]
final class RequiredUnlessRule implements RuleInterface, NativeCompilableInterface
{
    private string $otherField;

    /** @var list<mixed> */
    private array $values;

    /**
     * @param list<mixed> $values
     */
    public function __construct(string $otherField, array $values)
    {
        $this->otherField = $otherField;
        $this->values = $values;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $otherValue = $context->getValue($this->otherField);

        // If the other field's value is in the specified values, this field is NOT required
        if (DependentValues::matches($otherValue, $this->values)) {
            return null;
        }

        // Other field doesn't match specified values, so this field is required
        if ($this->isEmpty($value)) {
            return [
                'rule' => 'required_unless',
                'params' => [
                    'other' => $this->otherField,
                    'values' => DependentValues::describe($this->values),
                ],
            ];
        }

        return null;
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && \Vi\Validation\Execution\Emptiness::isBlankString($value)) {
            return true;
        }

        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $i = $context->indent;
        $params = "['other' => " . NativeCompilationContext::literal($this->otherField)
            . ", 'values' => " . NativeCompilationContext::literal(DependentValues::describe($this->values)) . ']';
        $matches = "\\Vi\\Validation\\Rules\\DependentValues::matches({$v}_o, " . NativeCompilationContext::literal($this->values) . ')';

        return "{$i}{$v}_o = " . NativeCompilationContext::lookup($this->otherField) . ";\n"
            . $context->emitError("!{$matches} && " . Emptiness::nativeExpression($v), 'required_unless', $params);
    }

    public function isImplicitForNative(): bool
    {
        return true;
    }
}
