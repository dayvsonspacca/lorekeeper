<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Command\Combat\Attack;
use Lorekeeper\Client\Command\Combat\ResurrectCommand;
use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Combat\Targetable;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Override;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use RuntimeException;

/**
 * @implements Script<null>
 *
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final readonly class KillScript implements Script
{
    private const array LISTENS = [
        MonstersDiedEvent::class,
        MonsterRewardedEvent::class,
        MonsterRespawnedEvent::class,
        PlayerDiedEvent::class,
        LeveledUpEvent::class,
    ];

    /**
     * @param Closure(): bool $until
     * @param Closure(): Timestamp $clock
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        private Character $me,
        private Cell $cell,
        private Pad $pad,
        private Attack $attack,
        private Closure $until,
        private Closure $clock,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, null> */
    #[Override]
    public function run(): Generator
    {
        $targets = $this->targets();

        $dead = [];
        $ready = [];
        while (!($this->until)()) {
            if (!$this->me->alive()) {
                yield from $this->resurrect();
                $dead = [];

                continue;
            }

            $target = self::firstAlive($targets, $dead);
            $now = ($this->clock)();
            $skill = $target === null ? null : $this->ready($ready, $now);
            if ($target !== null && $skill !== null) {
                yield new Send($this->attack->at($target)->with($skill->slot));
                $ready[$skill->slot->name] = $now->plus($skill->cooldown);
                $ready['next'] = $now->plus(Duration::seconds(1));
            }

            $answer = yield new Await(self::LISTENS, $this->wait($ready, $now));
            $dead = self::seen($answer, $dead);
        }

        return null;
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    private function resurrect(): Generator
    {
        yield new Pause(Duration::seconds(10));
        yield new Send(
            new ResurrectCommand($this->me->position()->area, $this->me->socket()),
            new Await([ResurrectedEvent::class], Duration::seconds(15)),
        );
        yield new Send(new MoveToCellCommand($this->me->position()->area, $this->cell, $this->pad));
        yield new Pause(Duration::seconds(1));
    }

    /**
     * @throws RuntimeException
     *
     * @return non-empty-list<Targetable>
     */
    private function targets(): array
    {
        $aimed = $this->attack->target();
        if (!$aimed instanceof MonsterSpawnId) {
            return [$aimed];
        }

        $placed = $this->me->map()->monstersIn($this->cell);
        $species = null;
        foreach ($placed as $placement) {
            if ($placement->id->value !== $aimed->value) {
                continue;
            }

            $species = $placement->monster->value;
        }

        if ($species === null) {
            throw new RuntimeException("Monster {$aimed->value} does not stand in {$this->cell->value}.");
        }

        $same = array_values(array_filter(
            array_map(
                static fn(MonsterPlacement $placement): MonsterSpawnId => $placement->id,
                array_filter(
                    $placed,
                    static fn(MonsterPlacement $placement): bool => $placement->monster->value === $species,
                ),
            ),
            static fn(MonsterSpawnId $spawn): bool => $spawn->value !== $aimed->value,
        ));

        return [$aimed, ...$same];
    }

    /** @param array<string, Timestamp> $ready */
    private function ready(array $ready, Timestamp $now): ?Skill
    {
        if (array_key_exists('next', $ready) && $ready['next']->after($now)) {
            return null;
        }

        $usable = array_filter(
            $this->me->skills(),
            fn(Skill $skill): bool => (
                $skill->target === SkillTarget::Enemy
                && !(array_key_exists($skill->slot->name, $ready) && $ready[$skill->slot->name]->after($now))
                && $skill->mana->value
                <= ($this->me->mana()->value ?? 100)
            ),
        );

        usort(
            $usable,
            static fn(Skill $a, Skill $b): int => (
                (int) ($a->slot === SkillSlot::AutoAttack) <=> (int) ($b->slot === SkillSlot::AutoAttack)
            ),
        );

        return $usable[0] ?? null;
    }

    /** @param array<string, Timestamp> $ready */
    private function wait(array $ready, Timestamp $now): Duration
    {
        $soonest = Duration::seconds(2);
        foreach ($ready as $at) {
            $left = $at->since($now);
            if ($left->isPositive() && $left->compare($soonest) < 0) {
                $soonest = $left;
            }
        }

        return $soonest->compare(Duration::milliseconds(200)) < 0 ? Duration::milliseconds(200) : $soonest;
    }

    /**
     * @param non-empty-list<Targetable> $targets
     * @param array<int, true> $dead
     */
    private static function firstAlive(array $targets, array $dead): ?Targetable
    {
        foreach ($targets as $target) {
            if (!$target instanceof MonsterSpawnId || !array_key_exists($target->value, $dead)) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @param array<int, true> $dead
     *
     * @return array<int, true>
     */
    private static function seen(null|Event|TimedOut $answer, array $dead): array
    {
        if ($answer instanceof MonstersDiedEvent) {
            foreach ($answer->monsters as $monster) {
                $dead[$monster->value] = true;
            }
        }

        if ($answer instanceof MonsterRewardedEvent) {
            $dead[$answer->monster->value] = true;
        }

        if ($answer instanceof MonsterRespawnedEvent) {
            unset($dead[$answer->id->value]);
        }

        return $dead;
    }
}
