<?php

declare(strict_types=1);

namespace Vi\Validation\Compilation;

/**
 * Shared codegen for SizeRule/BetweenRule: both measure the value the way their getSize() does
 * (number as a float, string by character count, array by element count, anything else 0) and
 * report the same type tag. Emits statements that leave the measure in `$size` and the tag in
 * `$type_tag`; the rule appends its own comparison.
 */
final class NativeSizeCompiler
{
    public static function emitSize(NativeCompilationContext $context, bool $isNumeric): string
    {
        $v = $context->valName;
        $i = $context->indent;
        // Under an explicit numeric rule (integer/numeric on the field) a numeric string is a number.
        $stringSize = $isNumeric ? "is_numeric({$v}) ? (float) {$v} : mb_strlen({$v})" : "mb_strlen({$v})";

        return "{$i}if (is_int({$v}) || is_float({$v})) {\n"
            . "{$i}    \$size = (float) {$v};\n"
            . "{$i}    \$type_tag = 'numeric';\n"
            . "{$i}} elseif (is_string({$v})) {\n"
            . "{$i}    \$size = {$stringSize};\n"
            . "{$i}    \$type_tag = 'string';\n"
            . "{$i}} elseif (is_array({$v})) {\n"
            . "{$i}    \$size = count({$v});\n"
            . "{$i}    \$type_tag = 'array';\n"
            . "{$i}} else {\n"
            . "{$i}    \$size = 0;\n"
            . "{$i}    \$type_tag = 'numeric';\n"
            . "{$i}}\n";
    }
}
