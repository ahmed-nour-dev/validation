<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use SplFileInfo;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::MAX_FILE_SIZE)]
final class MaxFileSizeRule implements RuleInterface
{
    private int $maxKilobytes;

    public function __construct(int $maxKilobytes)
    {
        $this->maxKilobytes = $maxKilobytes;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $sizeInKb = $this->getFileSizeInKilobytes($value);

        if ($sizeInKb === null || $sizeInKb > $this->maxKilobytes) {
            return ['rule' => 'max_file_size'];
        }

        return null;
    }

    private function getFileSizeInKilobytes(mixed $value): ?float
    {
        $bytes = $this->getFileSize($value);

        if ($bytes === null) {
            return null;
        }

        return $bytes / 1024;
    }

    private function getFileSize(mixed $value): ?int
    {
        // Handle SplFileInfo
        if ($value instanceof SplFileInfo) {
            if (!$value->isFile()) {
                return null;
            }
            return $value->getSize();
        }

        // Handle array (uploaded file format)
        if (is_array($value)) {
            if (isset($value['size']) && is_int($value['size'])) {
                return $value['size'];
            }
            if (isset($value['tmp_name']) && is_string($value['tmp_name']) && is_file($value['tmp_name'])) {
                return filesize($value['tmp_name']) ?: null;
            }
            return null;
        }

        // Handle string path
        if (is_string($value) && is_file($value)) {
            return filesize($value) ?: null;
        }

        return null;
    }
}
