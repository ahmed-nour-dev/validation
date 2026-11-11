<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * A validation rule. Public: implement it to pass your own rule objects to
 * FieldDefinition::rules(). See docs/public-api.md.
 */
interface RuleInterface
{
    /**
     * Return null when the value passes, or an error array when it fails. 'rule' names the
     * message key; 'message', when set, replaces the translated message.
     *
     * @return array{rule: string, message?: string}|null
     */
    public function validate(mixed $value, string $field, ValidationContext $context): ?array;
}
