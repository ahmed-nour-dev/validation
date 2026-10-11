<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

use Vi\Validation\Execution\ValidationContext;

#[RuleName(RuleId::MIN_FILE_SIZE)]
final class MinFileSizeRule implements RuleInterface
{
    private int $min;

    public function __construct(int $kb)
    {
        $this->min = $kb;
    }

    public function validate(mixed $value, string $field, ValidationContext $context): ?array
    {
        $path = $this->getPath($value);
        if ($path === null || !file_exists($path)) {
            return ['rule' => 'min_file_size', 'parameters' => ['min' => (string) $this->min]];
        }

        $size = filesize($path) / 1024; // KB

        if ($size < $this->min) {
            return ['rule' => 'min_file_size', 'parameters' => ['min' => (string) $this->min]];
        }

        return null;
    }

    private function getPath(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if ($value instanceof \SplFileInfo) {
            return $value->getPathname();
        }

        return null;
    }
}
