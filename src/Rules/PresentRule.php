<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

/**
 * The field under validation must be present in the input data (but can be empty).
 *
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
#[RuleName(RuleId::PRESENT)]
final class PresentRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $data = $context->getData();
        
        // Check if the field exists in the data using dot notation
        if (strpos($field, '.') === false) {
            if (!array_key_exists($field, $data)) {
                return ['rule' => 'present'];
            }
            return null;
        }

        // Handle nested fields
        $parts = explode('.', $field, 2);
        if (!isset($data[$parts[0]]) || !is_array($data[$parts[0]])) {
            return ['rule' => 'present'];
        }

        if (!array_key_exists($parts[1], $data[$parts[0]])) {
            return ['rule' => 'present'];
        }

        return null;
    }
}
