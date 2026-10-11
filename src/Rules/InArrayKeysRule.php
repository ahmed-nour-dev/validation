<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The value must be an array containing at least one of the listed keys (Laravel `in_array_keys`).
 */
#[RuleName(RuleId::IN_ARRAY_KEYS)]
final class InArrayKeysRule implements RuleInterface
{
    /** @var list<string> */
    private array $keys;

    public function __construct(string ...$keys)
    {
        $this->keys = array_values($keys);
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (is_array($value)) {
            foreach ($this->keys as $key) {
                if (array_key_exists($key, $value)) {
                    return null;
                }
            }
        }

        return ['rule' => 'in_array_keys', 'params' => ['values' => implode(', ', $this->keys)]];
    }
}
