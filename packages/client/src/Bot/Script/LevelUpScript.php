<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Spawn;
use Lorekeeper\Client\Command\Combat\AttackMonsterCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Server\Event\Event;
use Override;
use Psl\DateTime\Timestamp;
use RuntimeException;

final class LevelUpScript extends BotScript
{
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
        $this->from = $this->me->level()->value;

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
            yield from $this->kill(
                $cell,
                $pad,
                $this->attackIn($cell),
                fn(): bool => $this->me->level()->value >= $next,
            );
            yield $this->progress();
        }
    }

    /** @throws RuntimeException */
    private function attackIn(Cell $cell): AttackMonsterCommand
    {
        foreach ($this->me->map()->cells() as $mapCell) {
            $spawn = $mapCell->spawns[0] ?? null;
            if ($mapCell->cell->value !== $cell->value || $spawn === null) {
                continue;
            }

            return new AttackMonsterCommand($this->me->position()->area, SkillSlot::AutoAttack, $spawn->id);
        }

        throw new RuntimeException("There are no monsters in {$cell->value}.");
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
        $best = new Cell('r4');
        $bestLevel = 0;
        foreach ($this->me->map()->cells() as $mapCell) {
            $level = max(0, ...array_map(
                static fn(Spawn $spawn): int => $spawn->monster->level->value,
                $mapCell->spawns,
            ));
            if ($level > $this->me->level()->value || $level <= $bestLevel) {
                continue;
            }

            $best = $mapCell->cell;
            $bestLevel = $level;
        }

        return $best;
    }
}
