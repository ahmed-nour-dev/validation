<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use DateTimeImmutable;
use Vi\Validation\Compilation\NativeCompilationContext;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::DATE_FORMAT)]
final class DateFormatRule implements RuleInterface, NativeCompilableInterface
{
    public function __construct(private string $format)
    {
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_string($value) && !is_numeric($value)) {
            return ['rule' => 'date_format', 'parameters' => ['format' => $this->format]];
        }

        $value = (string) $value;
        if (str_contains($value, "\0")) {
            // createFromFormat() throws ValueError on NUL bytes; never a valid date.
            return ['rule' => 'date_format', 'parameters' => [0 => $this->format, 'format' => $this->format]];
        }
        $date = DateTimeImmutable::createFromFormat($this->format, $value);

        if ($date === false || $date->format($this->format) !== $value) {
            return ['rule' => 'date_format', 'parameters' => [0 => $this->format, 'format' => $this->format]];
        }

        return null;
    }

    public function compileNative(NativeCompilationContext $context): string
    {
        $v = $context->valName;
        $i = $context->indent;
        $format = NativeCompilationContext::literal($this->format);
        $inner = $context->withIndent($i . '    ');

        // Same three outcomes as validate(), including its two different `parameters` shapes.
        return "{$i}if (!is_string({$v}) && !is_numeric({$v})) {\n"
            . $inner->errorStatement('date_format', "['format' => {$format}]")
            . "{$i}} else {\n"
            . "{$i}    \$df_str = (string) {$v};\n"
            . "{$i}    \$df_date = str_contains(\$df_str, \"\\0\") ? false : \\DateTimeImmutable::createFromFormat({$format}, \$df_str);\n"
            . "{$i}    if (\$df_date === false || \$df_date->format({$format}) !== \$df_str) {\n"
            . $context->withIndent($i . '        ')->errorStatement('date_format', "[0 => {$format}, 'format' => {$format}]")
            . "{$i}    }\n"
            . "{$i}}\n";
    }

    public function isImplicitForNative(): bool
    {
        return false;
    }
}
