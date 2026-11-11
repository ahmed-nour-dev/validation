<?php

declare(strict_types=1);

namespace Vi\Validation\Messages;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
final class MessageBag
{
    /** @var array<string, list<string>> */
    private array $messages = [];

    public function add(string $key, string $message): void
    {
        $this->messages[$key][] = $message;
    }

    public function has(string $key): bool
    {
        return isset($this->messages[$key]) && count($this->messages[$key]) > 0;
    }

    public function first(?string $key = null): ?string
    {
        if ($key !== null) {
            return $this->messages[$key][0] ?? null;
        }

        foreach ($this->messages as $messages) {
            if (!empty($messages)) {
                return $messages[0];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function get(string $key): array
    {
        return $this->messages[$key] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->messages);
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->messages as $messages) {
            $count += count($messages);
        }
        return $count;
    }

    public function isEmpty(): bool
    {
        return $this->messages === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @param array<string, list<string>> $messages
     */
    public function merge(array $messages): void
    {
        foreach ($messages as $key => $keyMessages) {
            foreach ($keyMessages as $message) {
                $this->add($key, $message);
            }
        }
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
