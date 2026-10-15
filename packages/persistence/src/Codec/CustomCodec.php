<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Closure;
use Override;

final readonly class CustomCodec implements ValueCodec
{
    /**
     * @param non-empty-string $key
     * @param class-string $class
     * @param Closure(object): mixed $encode
     * @param Closure(mixed): object $decode
     */
    public function __construct(
        private string $key,
        private string $class,
        private Closure $encode,
        private Closure $decode,
    ) {}

    #[Override]
    public function key(): string
    {
        return $this->key;
    }

    #[Override]
    public function class(): string
    {
        return $this->class;
    }

    #[Override]
    public function encode(object $value): mixed
    {
        return ($this->encode)($value);
    }

    #[Override]
    public function decode(mixed $payload): object
    {
        return ($this->decode)($payload);
    }
}
