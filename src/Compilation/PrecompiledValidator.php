<?php

declare(strict_types=1);

namespace Vi\Validation\Compilation;

use Vi\Validation\Execution\CompiledSchema;
use Vi\Validation\Execution\ValidationResult;
use Vi\Validation\Execution\ValidatorEngine;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
final class PrecompiledValidator
{
    private CompiledSchema $schema;
    private ValidatorEngine $engine;
    private string $identifier;

    public function __construct(
        CompiledSchema $schema,
        string $identifier,
        ?ValidatorEngine $engine = null
    ) {
        $this->schema = $schema;
        $this->identifier = $identifier;
        $this->engine = $engine ?? new ValidatorEngine();
    }

    /**
     * Validate data against the precompiled schema.
     *
     * @param array<string, mixed> $data
     */
    public function validate(array $data): ValidationResult
    {
        return $this->engine->validate($this->schema, $data);
    }

    /**
     * Get the schema identifier.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the compiled schema.
     */
    public function getSchema(): CompiledSchema
    {
        return $this->schema;
    }

    /**
     * Create a precompiled validator from a file.
     */
    public static function fromFile(string $path): ?self
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = unserialize($content);

        if (!is_array($data) || !isset($data['schema'], $data['identifier'])) {
            return null;
        }

        if (!$data['schema'] instanceof CompiledSchema) {
            return null;
        }

        return new self($data['schema'], $data['identifier']);
    }

    /**
     * Save the precompiled validator to a file.
     */
    public function saveToFile(string $path): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'schema' => $this->schema,
            'identifier' => $this->identifier,
        ];

        return file_put_contents($path, serialize($data), LOCK_EX) !== false;
    }
}
