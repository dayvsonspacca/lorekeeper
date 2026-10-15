<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Lorekeeper\Core\Support\NonEmptyString;
use Override;
use Psl\Type;

final readonly class StringCodec implements ValueCodec
{
    /**
     * @param non-empty-string $key
     * @param class-string<NonEmptyString> $class
     */
    public function __construct(
        private string $key,
        private string $class,
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
    public function encode(object $value): string
    {
        return Type\instance_of(NonEmptyString::class)->assert($value)->value;
    }

    #[Override]
    public function decode(mixed $payload): NonEmptyString
    {
        return new $this->class(Type\non_empty_string()->assert($payload));
    }
}
