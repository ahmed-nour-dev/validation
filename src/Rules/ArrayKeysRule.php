<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The value must be an array whose keys are all in the allowed list (Laravel `array_keys`).
 *
 * This is the same check as `array:key1,key2`, as a dedicated rule with its own message that also
 * names the offending keys (`:unexpected`).
 */
#[RuleName(RuleId::ARRAY_KEYS)]
final class ArrayKeysRule implements RuleInterface
{
    /** @var list<string> */
    private array $keys;

    public function __construct(string ...$keys)
    {
        $this->keys = array_values($keys);
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $unexpected = is_array($value) ? array_keys(array_diff_key($value, array_fill_keys($this->keys, ''))) : [];

        if (is_array($value) && $unexpected === []) {
            return null;
        }

        return ['rule' => 'array_keys', 'params' => [
            'values' => implode(', ', $this->keys),
            'unexpected' => implode(', ', $unexpected),
        ]];
    }
}
