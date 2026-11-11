<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::REQUIRED_ARRAY_KEYS)]
final class RequiredArrayKeysRule implements RuleInterface
{
    /** @var string[] */
    private array $keys;

    public function __construct(string ...$keys)
    {
        $this->keys = $keys;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!is_array($value)) {
            return ['rule' => 'required_array_keys', 'parameters' => array_values($this->keys)];
        }

        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $value)) {
                return ['rule' => 'required_array_keys', 'parameters' => array_values($this->keys)];
            }
        }

        return null;
    }
}
