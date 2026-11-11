<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * A rule that wraps other rules and only executes them if a condition is met.
 *
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::CONDITIONAL)]
final class ConditionalRule implements RuleInterface
{
    /** @var bool|callable */
    private $condition;

    /** @var list<RuleInterface> */
    private array $rules;

    /** @var list<RuleInterface> */
    private array $elseRules;

    /**
     * @param bool|callable $condition
     * @param list<RuleInterface> $rules
     * @param list<RuleInterface> $elseRules
     */
    public function __construct($condition, array $rules, array $elseRules = [])
    {
        $this->condition = $condition;
        $this->rules = $rules;
        $this->elseRules = $elseRules;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $conditionResult = is_callable($this->condition) 
            ? ($this->condition)($context->getData(), $context) 
            : $this->condition;

        $activeRules = $conditionResult ? $this->rules : $this->elseRules;

        foreach ($activeRules as $rule) {
            $error = $rule->validate($value, $field, $context);
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @return list<RuleInterface>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return list<RuleInterface>
     */
    public function getElseRules(): array
    {
        return $this->elseRules;
    }
}
