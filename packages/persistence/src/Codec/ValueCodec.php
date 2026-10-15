<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

interface ValueCodec
{
    /** @return non-empty-string */
    public function key(): string;

    /** @return class-string */
    public function class(): string;

    public function encode(object $value): mixed;

    public function decode(mixed $payload): object;
}
