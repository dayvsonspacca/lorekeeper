<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Fights;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterName;
use Override;
use Psl\DateTime\Duration;

final readonly class Hunt implements Action
{
    public function __construct(
        public MapSpot $spot,
        public ?MonsterName $monster,
        public ItemId $item,
        public Quantity $quantity,
        public Duration $limit,
    ) {}

    #[Override]
    public function describe(): string
    {
        $who = $this->monster === null ? '' : "{$this->monster->value} ";

        return "Hunt {$who}in {$this->spot->cell->value} for item {$this->item->value} ×{$this->quantity->value}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        $hunting = $bot->hunt(
            $this->spot,
            $bot->attackIn($this->spot->cell, $this->monster),
            $this->item,
            $this->quantity,
            $this->limit,
        );
        yield from $hunting;

        return $hunting->getReturn()
            ? null
            : new Failed("item {$this->item->value} ×{$this->quantity->value} was not held after {$this->minutes()}");
    }

    private function minutes(): string
    {
        $minutes = (int) $this->limit->getTotalMinutes();

        return $minutes === 1 ? '1 minute' : "{$minutes} minutes";
    }
}
