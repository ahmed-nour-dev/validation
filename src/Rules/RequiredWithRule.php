<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\Emptiness;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::REQUIRED_WITH)]
final class RequiredWithRule implements RuleInterface, NativeCompilableInterface
{
    /** @var list<string> */
    private array $otherFields;

    /**
     * @param list<string> $otherFields
     */
    public function __construct(array $otherFields)
    {
        $this->otherFields = $otherFields;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        // Check if any of the other fields is present
        $anyPresent = false;
        foreach ($this->otherFields as $otherField) {
            $otherValue = $context->getValue($otherField);
            if (!$this->isEmpty($otherValue)) {
                $anyPresent = true;
                break;
            }
        }

        // If none of the other fields are present, this field is not required
        if (!$anyPresent) {
            return null;
        }

        // At least one of the other fields is present, so this field is required
        if ($this->isEmpty($value)) {
            return [
                'rule' => 'required_with',
                'params' => [
                    'values' => implode(', ', $this->otherFields),
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
        $any = $v . '_any';
        $o = $v . '_o';
        $params = "['values' => " . NativeCompilationContext::literal(implode(', ', $this->otherFields)) . ']';

        // required when any other field is non-empty
        $code = "{$i}{$any} = false;\n";
        foreach ($this->otherFields as $n => $other) {
            $pad = $n === 0 ? $i : $i . '    ';
            if ($n > 0) {
                $code .= "{$i}if (!{$any}) {\n";
            }
            $code .= "{$pad}{$o} = " . NativeCompilationContext::lookup($other) . ";\n"
                . "{$pad}{$any} = !" . Emptiness::nativeExpression($o) . ";\n";
            if ($n > 0) {
                $code .= "{$i}}\n";
            }
        }

        return $code . $context->emitError("{$any} && " . Emptiness::nativeExpression($v), 'required_with', $params);
    }

    public function isImplicitForNative(): bool
    {
        return true;
    }
}
