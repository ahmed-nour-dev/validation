<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use SplFileInfo;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::IMAGE)]
final class ImageRule implements RuleInterface
{
    /** @var list<string> */
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/svg+xml',
        'image/webp',
    ];

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        if (!$this->isValidImage($value)) {
            return ['rule' => 'image'];
        }

        return null;
    }

    private function isValidImage(mixed $value): bool
    {
        $mimeType = $this->getMimeType($value);

        if ($mimeType === null) {
            return false;
        }

        return in_array($mimeType, self::IMAGE_MIMES, true);
    }

    private function getMimeType(mixed $value): ?string
    {
        // Handle SplFileInfo
        if ($value instanceof SplFileInfo) {
            $path = $value->getPathname();
            if (!is_file($path)) {
                return null;
            }
            return mime_content_type($path) ?: null;
        }

        // Handle array (uploaded file format)
        if (is_array($value) && isset($value['tmp_name'])) {
            if (!is_string($value['tmp_name']) || !is_file($value['tmp_name'])) {
                return null;
            }
            return mime_content_type($value['tmp_name']) ?: null;
        }

        // Handle string path
        if (is_string($value) && is_file($value)) {
            return mime_content_type($value) ?: null;
        }

        return null;
    }
}
