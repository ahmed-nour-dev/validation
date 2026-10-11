<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The array must not contain any of the listed values (Laravel `doesnt_contain`).
 *
 * Unlike `contains`, Laravel compares strictly here, so the string parameter `1` does not match
 * the integer `1` in the array.
 */
#[RuleName(RuleId::DOESNT_CONTAIN)]
final class DoesntContainRule implements RuleInterface
{
    /**
     * @param list<string> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (is_array($value)) {
            foreach ($this->values as $needle) {
                if (in_array($needle, $value, true)) {
                    return $this->failure();
                }
            }

            return null;
        }

        return $this->failure();
    }

    /**
     * @return array{rule: string, params: array{values: string}}
     */
    private function failure(): array
    {
        return ['rule' => 'doesnt_contain', 'params' => ['values' => implode(', ', $this->values)]];
    }
}
