<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use InvalidArgumentException;
use Vi\Validation\Execution\ValidationContext;

/**
 * The value must be valid in the given character encoding (Laravel `encoding:utf-8`).
 *
 * Laravel throws at validation time for an encoding mbstring doesn't know; this rule throws when
 * it is built instead, so a typo such as `encoding:utf8` fails at schema-build time rather than
 * on the first row.
 */
#[RuleName(RuleId::ENCODING)]
final class EncodingRule implements RuleInterface
{
    public function __construct(private readonly string $encoding)
    {
        $known = array_map('mb_strtolower', mb_list_encodings());
        if (!in_array(mb_strtolower($encoding), $known, true)) {
            throw new InvalidArgumentException(
                "Validation rule encoding parameter [{$encoding}] is not a valid encoding."
            );
        }
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            $value = (string) $value;
        }

        if ((is_string($value) || is_array($value)) && mb_check_encoding($value, $this->encoding)) {
            return null;
        }

        return ['rule' => 'encoding', 'params' => ['encoding' => $this->encoding]];
    }
}
