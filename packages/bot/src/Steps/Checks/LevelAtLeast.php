<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Checks;

use Generator;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\Player\PlayerLevel;
use Override;

final readonly class LevelAtLeast implements Check
{
    public function __construct(
        public PlayerLevel $level,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Level {$this->level->value} or higher";
    }

    #[Override]
    public function holds(StepScript $bot): Generator
    {
        yield from [];

        return $bot->me()->player()->progress->level->value >= $this->level->value;
    }
}
