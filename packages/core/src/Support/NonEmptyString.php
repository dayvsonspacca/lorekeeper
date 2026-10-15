<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use Psl\Type;
use Stringable;

abstract readonly class NonEmptyString implements Stringable
{
    /** @var non-empty-string */
    public string $value;

    final public function __construct(string $value)
    {
        $this->value = Type\non_empty_string()->assert($value);
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
