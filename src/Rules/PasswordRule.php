<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::PASSWORD)]
final class PasswordRule implements RuleInterface
{
    private int $min = 8;
    private bool $mixedCase = false;
    private bool $letters = false;
    private bool $numbers = false;
    private bool $symbols = false;

    public function min(int $min): self
    {
        $this->min = $min;
        return $this;
    }

    public function mixedCase(): self
    {
        $this->mixedCase = true;
        return $this;
    }

    public function letters(): self
    {
        $this->letters = true;
        return $this;
    }

    public function numbers(): self
    {
        $this->numbers = true;
        return $this;
    }

    public function symbols(): self
    {
        $this->symbols = true;
        return $this;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if ($value === null || !is_string($value)) {
            return null;
        }

        if (strlen($value) < $this->min) {
            return ['rule' => 'password', 'parameters' => ['min' => (string) $this->min]];
        }

        if ($this->letters && !preg_match('/\pL/u', $value)) {
            return ['rule' => 'password_letters'];
        }

        if ($this->mixedCase && (!preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value))) {
            return ['rule' => 'password_mixed'];
        }

        if ($this->numbers && !preg_match('/\pN/u', $value)) {
            return ['rule' => 'password_numbers'];
        }

        if ($this->symbols && !preg_match('/\p{Z}|\p{S}|\p{P}/u', $value)) {
            return ['rule' => 'password_symbols'];
        }

        return null;
    }
}
