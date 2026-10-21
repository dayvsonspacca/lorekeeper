<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Fights;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterName;
use Override;
use Psl\DateTime\Duration;

final readonly class FightFor implements Action
{
    public function __construct(
        public MapSpot $spot,
        public ?MonsterName $monster,
        public Duration $time,
    ) {}

    #[Override]
    public function describe(): string
    {
        $who = $this->monster === null ? '' : " {$this->monster->value}";
        $minutes = (int) $this->time->getTotalMinutes();

        return "Fight{$who} in {$this->spot->cell->value} for {$minutes} min";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        $deadline = $bot->now()->plus($this->time);
        yield from $bot->kill(
            $this->spot,
            $bot->attackIn($this->spot->cell, $this->monster),
            static fn(): bool => !$bot->now()->before($deadline),
        );

        return null;
    }
}
