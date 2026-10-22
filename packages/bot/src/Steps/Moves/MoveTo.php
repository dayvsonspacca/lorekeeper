<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Moves;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\World\Map\MapSpot;
use Override;

final readonly class MoveTo implements Action
{
    public function __construct(
        public MapSpot $spot,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Move to {$this->spot->cell->value}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        yield from $bot->moveTo($this->spot);

        return null;
    }
}
