<?php

declare(strict_types=1);

namespace Vi\Validation\Messages;

final class MessageResolver
{
    private TranslatorInterface $translator;

    /** @var array<string, string> */
    private array $customMessages = [];

    /** @var array<string, string> */
    private array $customAttributes = [];

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator ?? new Translator();
    }

    /**
     * Resolve the error message for a validation rule.
     *
     * @param array<string, mixed> $params
     */
    public function resolve(string $field, string $rule, array $params = []): string
    {
        // Check for custom message first (field.rule format)
        $customKey = $field . '.' . $rule;
        $customMessage = $this->customMessages[$customKey]
            ?? $this->matchWildcard($this->customMessages, $customKey);
        if ($customMessage !== null) {
            return $this->replaceAttributes(
                $customMessage,
                $field,
                $params
            );
        }

        // Check for rule-level custom message
        if (isset($this->customMessages[$rule])) {
            return $this->replaceAttributes(
                $this->customMessages[$rule],
                $field,
                $params
            );
        }

        // Get from translator
        $message = $this->getTranslatedMessage($rule, $params);

        return $this->replaceAttributes($message, $field, $params);
    }

    /**
     * @param array<string, string> $messages
     */
    public function setCustomMessages(array $messages): void
    {
        $this->customMessages = $messages;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function setCustomAttributes(array $attributes): void
    {
        $this->customAttributes = $attributes;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getTranslatedMessage(string $rule, array $params): string
    {
        // Handle rules with type-specific messages (min, max, size, between)
        $typeSpecificRules = ['min', 'max', 'size', 'between'];

        if (in_array($rule, $typeSpecificRules, true) && isset($params['type'])) {
            $type = $params['type'];
            $key = $rule . '.' . $type;
            $message = $this->translator->get($key);

            // If we got back the key itself, fall back to default
            if ($message !== $key) {
                return $message;
            }
        }

        return $this->translator->get($rule);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function replaceAttributes(string $message, string $field, array $params): string
    {
        $attribute = $this->customAttributes[$field]
            ?? $this->matchWildcard($this->customAttributes, $field)
            ?? $this->formatAttribute($field);

        $replacements = array_merge($params, ['attribute' => $attribute]);

        foreach ($replacements as $key => $value) {
            $key = (string) $key;
            if (is_scalar($value)) {
                $message = str_replace(
                    [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                    [(string) $value, strtoupper((string) $value), ucfirst((string) $value)],
                    $message
                );
            }
        }

        return $message;
    }

    /**
     * A custom message/attribute registered under a wildcard key ('items.*.sku.required',
     * 'items.*.sku') for a concrete path ('items.0.sku.required'), like Laravel's Str::is().
     *
     * @param array<string, string> $custom
     */
    private function matchWildcard(array $custom, string $key): ?string
    {
        if (strpos($key, '.') === false) {
            return null;
        }

        foreach ($custom as $pattern => $text) {
            $pattern = (string) $pattern;
            if (strpos($pattern, '*') !== false
                && preg_match('/^' . str_replace('\\*', '.*', preg_quote($pattern, '/')) . '$/u', $key) === 1
            ) {
                return $text;
            }
        }

        return null;
    }

    private function formatAttribute(string $field): string
    {
        return str_replace(['_', '-'], ' ', $field);
    }
}
