<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Asset;

use InvalidArgumentException;
use Psl\Type;
use Stringable;

final readonly class Gamefile implements Stringable
{
    /** @var non-empty-string */
    public string $value;

    public function __construct(string $value)
    {
        $value = Type\non_empty_string()->assert($value);

        if (!str_ends_with($value, '.swf')) {
            throw new InvalidArgumentException("Gamefile must end with '.swf', got '{$value}'.");
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
