<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Scripts;

use Closure;
use Generator;
use Lorekeeper\Bot\Abilities\Moves\Joins;
use Lorekeeper\Bot\Abilities\Moves\Kills;
use Lorekeeper\Bot\BotScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use Override;
use Psl\DateTime\Timestamp;

final class LevelUpScript extends BotScript
{
    use Joins;
    use Kills;

    private int $from = 1;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(
        private readonly PlayerLevel $target,
        ?Closure $clock = null,
    ) {
        parent::__construct($clock);
    }

    #[Override]
    public function title(): string
    {
        return "Level up to {$this->target->value}";
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done> */
    #[Override]
    protected function rules(): Generator
    {
        $this->from = $this->level();

        if ($this->below(10)) {
            yield from $this->join(new MapName('oaklore'));
            yield from $this->levelUpIn(new MapSpot(new MapCell('r3'), MapPad::Left), upTo: 10);
        }

        if ($this->below(20)) {
            yield from $this->join(new MapName('swordhavenundead'));
            yield from $this->levelUpIn(new MapSpot(new MapCell('Gates'), MapPad::Left), upTo: 20);
        }

        if ($this->below(75)) {
            yield from $this->join(new MapName('icestormarena'));
            yield from $this->levelUpIn(new MapSpot(new MapCell('r7'), MapPad::Left), upTo: 25);
            yield from $this->levelUpIn(new MapSpot(new MapCell('r10'), MapPad::Left), upTo: 30);
            yield from $this->levelUpIn(new MapSpot(new MapCell('r11'), MapPad::Left), upTo: 35);
            yield from $this->levelUpIn(new MapSpot(new MapCell('r14'), MapPad::Left), upTo: 50);
            yield from $this->levelUpIn(new MapSpot(new MapCell('r16'), MapPad::Left), upTo: 61);
            yield from $this->levelUpIn(new MapSpot(new MapCell('r17'), MapPad::Left), upTo: 75);
        }

        if ($this->below(100)) {
            yield from $this->join(new MapName('icestormunder'));
            yield from $this->levelUpIn(new MapSpot(new MapCell('r2'), MapPad::Top), upTo: 100);
        }

        return new Done("Reached level {$this->level()}");
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    private function levelUpIn(MapSpot $spot, int $upTo): Generator
    {
        while ($this->below($upTo)) {
            $next = $this->level() + 1;
            yield from $this->kill($spot, $this->attackIn($spot->cell), fn(): bool => $this->level() >= $next);
            yield $this->progress();
        }
    }

    private function level(): int
    {
        return $this->me()->player()->progress->level->value;
    }

    private function below(int $level): bool
    {
        return $this->level() < min($level, $this->target->value);
    }

    private function progress(): Progress
    {
        $total = max(1, $this->target->value - $this->from);

        return new Progress(max(0, min($total, $this->level() - $this->from)), $total);
    }
}
