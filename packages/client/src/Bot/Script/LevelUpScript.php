<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Override;
use Psl\DateTime\Timestamp;

final readonly class LevelUpScript extends BotScript
{
    private int $from;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(
        Character $me,
        private PlayerLevel $target,
        ?Closure $clock = null,
    ) {
        parent::__construct($me, $clock);
        $this->from = $me->level()->value;
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done> */
    #[Override]
    protected function play(): Generator
    {
        if ($this->below(10)) {
            yield from $this->join(new MapName('oaklore'));
            yield from $this->levelUpIn(new Cell('r3'), Pad::Left, upTo: 10);
        }

        if ($this->below(20)) {
            yield from $this->join(new MapName('swordhavenundead'));
            yield from $this->levelUpIn(new Cell('Gates'), Pad::Left, upTo: 20);
        }

        while ($this->below(100)) {
            yield from $this->join(new MapName('icestormarena'));
            yield from $this->levelUpIn($this->hardestCellForMe(), Pad::Left, upTo: $this->me->level()->value + 1);
        }

        return new Done("Reached level {$this->me->level()->value}");
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    private function levelUpIn(Cell $cell, Pad $pad, int $upTo): Generator
    {
        while ($this->below($upTo)) {
            $next = $this->me->level()->value + 1;
            yield from $this->kill($cell, $pad, fn(): bool => $this->me->level()->value >= $next);
            yield $this->progress();
        }
    }

    private function below(int $level): bool
    {
        return $this->me->level()->value < min($level, $this->target->value);
    }

    private function progress(): Progress
    {
        $total = max(1, $this->target->value - $this->from);

        return new Progress(max(0, min($total, $this->me->level()->value - $this->from)), $total);
    }

    private function hardestCellForMe(): Cell
    {
        $best = null;
        $bestLevel = 0;
        foreach ($this->me->map()->cells() as $cell) {
            $levels = array_map(
                static fn(MonsterPlacement $placement): int => $placement->level->value ?? 0,
                $this->me->map()->monstersIn($cell),
            );
            $level = max($levels);
            if ($level <= $this->me->level()->value && $level > $bestLevel) {
                $best = $cell;
                $bestLevel = $level;
            }
        }

        return $best ?? $this->me->map()->cells()[0] ?? new Cell('r4');
    }
}
