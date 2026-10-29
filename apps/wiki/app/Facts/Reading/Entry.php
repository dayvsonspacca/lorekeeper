<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts\Reading;

final readonly class Entry
{
    /** @param array<array-key, mixed> $data */
    public function __construct(
        public array $data,
    ) {}

    public function int(string $key): ?int
    {
        $value = $this->data[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : null;
    }

    /** @return null|non-empty-string */
    public function text(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    public function flag(string $key): bool
    {
        $value = $this->data[$key] ?? false;

        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
