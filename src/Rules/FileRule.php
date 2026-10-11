<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use SplFileInfo;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::FILE)]
final class FileRule implements RuleInterface
{
    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!$this->isValidFile($value)) {
            return ['rule' => 'file'];
        }

        return null;
    }

    private function isValidFile(mixed $value): bool
    {
        // Handle SplFileInfo
        if ($value instanceof SplFileInfo) {
            return $value->isFile();
        }

        // Handle array (uploaded file format)
        if (is_array($value)) {
            return isset($value['tmp_name']) && is_uploaded_file($value['tmp_name']);
        }

        // Handle string path
        if (is_string($value)) {
            return is_file($value);
        }

        return false;
    }
}
