<?php

declare(strict_types=1);

namespace Vi\Validation\Execution;

use Vi\Validation\Rules\DatabaseValidatorInterface;
use Vi\Validation\Rules\PasswordHasherInterface;

final class ValidationContext
{
    /** @var array<string, mixed> */
    private array $data;

    private ErrorCollector $errors;

    /** @var list<string> */
    private array $wildcardKeys = [];
    private string $wildcardPattern = '';

    /** @var array<string, array<string, mixed>> */
    private array $wildcardValues = [];

    private ?DatabaseValidatorInterface $databaseValidator = null;
    private ?PasswordHasherInterface $passwordHasher = null;

    /**
     * @param array<string, mixed> $data
     * @param ErrorCollector $errors
     */
    public function __construct(array $data, ErrorCollector $errors)
    {
        $this->data = $data;
        $this->errors = $errors;
    }

    public function getDatabaseValidator(): ?DatabaseValidatorInterface
    {
        return $this->databaseValidator;
    }

    public function setDatabaseValidator(?DatabaseValidatorInterface $databaseValidator): void
    {
        $this->databaseValidator = $databaseValidator;
    }

    public function getPasswordHasher(): ?PasswordHasherInterface
    {
        return $this->passwordHasher;
    }

    public function setPasswordHasher(?PasswordHasherInterface $passwordHasher): void
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Keys the current wildcard field's '*' segments matched ('items.*.sku' => 'items.3.sku'
     * gives ['3']). Sibling references such as `same:items.*.other` have their '*' replaced by
     * these in order, like Laravel's replaceAsterisksInParameters(). Empty outside wildcards.
     *
     * @param list<string> $keys
     */
    public function setWildcardKeys(array $keys, string $pattern = ''): void
    {
        $this->wildcardKeys = $keys;
        $this->wildcardPattern = $pattern;
    }

    /**
     * Every value a wildcard path ('items.*.id') addresses, keyed by concrete path. Expanded
     * once per payload and memoized, so cross-element rules such as `distinct` don't re-scan
     * the data for every element.
     *
     * @return array<string, mixed>
     */
    public function wildcardValues(string $pattern): array
    {
        if (!isset($this->wildcardValues[$pattern])) {
            $values = [];
            foreach (DataHelper::expand($this->data, $pattern) as [$path]) {
                $values[$path] = DataHelper::get($this->data, $path);
            }
            $this->wildcardValues[$pattern] = $values;
        }

        return $this->wildcardValues[$pattern];
    }

    /**
     * The wildcard rule key being expanded ('items.*.sku'), or '' for a plain field.
     */
    public function getWildcardPattern(): string
    {
        return $this->wildcardPattern;
    }

    /**
     * Replace the '*' segments of a reference path with the current wildcard keys.
     */
    public function bind(string $field): string
    {
        if ($this->wildcardKeys === [] || strpos($field, '*') === false) {
            return $field;
        }

        $i = 0;
        $keys = $this->wildcardKeys;

        return (string) preg_replace_callback(
            '/\*/',
            static function (array $m) use (&$i, $keys): string {
                return $keys[$i++] ?? '*';
            },
            $field
        );
    }

    public function getValue(string $field): mixed
    {
        $field = $this->bind($field);

        if (strpos($field, '.') === false) {
            return $this->data[$field] ?? null;
        }

        return DataHelper::get($this->data, $field);
    }

    public function hasValue(string $field): bool
    {
        $field = $this->bind($field);

        if (strpos($field, '.') === false) {
            return array_key_exists($field, $this->data);
        }

        return DataHelper::has($this->data, $field);
    }

    /**
     * Get all validation data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->wildcardValues = [];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function addError(string $field, string $rule, ?string $message = null, array $params = []): void
    {
        $this->errors->add($field, $rule, $message, $params);
    }
}
