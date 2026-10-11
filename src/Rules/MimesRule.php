<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use SplFileInfo;
use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::MIMES)]
final class MimesRule implements RuleInterface
{
    /** @var list<string> */
    private array $allowedMimes;

    /** @var array<string, string> */
    private const EXTENSION_TO_MIME = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
    ];

    /** @param list<string> $allowedMimes */
    public function __construct(array $allowedMimes)
    {
        $this->allowedMimes = $this->normalizeMimes($allowedMimes);
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $mimeType = $this->getMimeType($value);

        if ($mimeType === null || !in_array($mimeType, $this->allowedMimes, true)) {
            return ['rule' => 'mimes'];
        }

        return null;
    }

    /**
     * @param list<string> $mimes
     * @return list<string>
     */
    private function normalizeMimes(array $mimes): array
    {
        $normalized = [];

        foreach ($mimes as $mime) {
            // If it's an extension, convert to MIME type
            if (isset(self::EXTENSION_TO_MIME[$mime])) {
                $normalized[] = self::EXTENSION_TO_MIME[$mime];
            } elseif (str_contains($mime, '/')) {
                // Already a MIME type
                $normalized[] = $mime;
            }
        }

        return array_values(array_unique($normalized));
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
