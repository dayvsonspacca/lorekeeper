<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Fights;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterName;
use Override;

final readonly class FightToLevel implements Action
{
    public function __construct(
        public MapSpot $spot,
        public ?MonsterName $monster,
        public PlayerLevel $level,
    ) {}

    #[Override]
    public function describe(): string
    {
        $who = $this->monster === null ? '' : " {$this->monster->value}";

        return "Fight{$who} in {$this->spot->cell->value} until level {$this->level->value}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        $reached = fn(): bool => $bot->me()->hero()->level->value >= $this->level->value;
        if (!$reached()) {
            yield from $bot->kill($this->spot, $bot->attackIn($this->spot->cell, $this->monster), $reached);
        }

        return null;
    }
}
