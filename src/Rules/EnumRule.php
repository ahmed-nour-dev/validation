<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;
use UnitEnum;

#[RuleName(RuleId::ENUM)]
final class EnumRule implements RuleInterface
{
    /** @var class-string<\UnitEnum> */
    private string $enumClass;

    /**
     * @param class-string<\UnitEnum> $enumClass
     */
    public function __construct(string $enumClass)
    {
        $this->enumClass = $enumClass;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!enum_exists($this->enumClass)) {
            return ['rule' => 'enum'];
        }

        try {
            foreach ($this->enumClass::cases() as $case) {
                if (isset($case->value) && $case->value === $value) {
                    return null;
                }
                if ($case->name === $value) {
                    return null;
                }
            }
        } catch (\Throwable) {
            return ['rule' => 'enum'];
        }

        return ['rule' => 'enum'];
    }
}
