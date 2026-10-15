<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Closure;
use Override;
use Psl\Type;

final readonly class IntegerCodec implements ValueCodec
{
    /**
     * @param non-empty-string $key
     * @param class-string $class
     * @param Closure(int): object $create
     */
    public function __construct(
        private string $key,
        private string $class,
        private Closure $create,
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
    public function encode(object $value): int
    {
        return Type\shape(['value' => Type\int()])->assert(get_object_vars($value))['value'];
    }

    #[Override]
    public function decode(mixed $payload): object
    {
        return ($this->create)(Type\int()->assert($payload));
    }
}
