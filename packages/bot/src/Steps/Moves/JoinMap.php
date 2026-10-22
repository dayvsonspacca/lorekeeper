<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Moves;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\World\Map\MapName;
use Override;

final readonly class JoinMap implements Action
{
    public function __construct(
        public MapName $map,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Join {$this->map->value}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        yield from $bot->join($this->map);

        return null;
    }
}
