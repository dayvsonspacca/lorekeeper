<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Command\Combat\Attack;
use Lorekeeper\Client\Command\Combat\ResurrectCommand;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
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
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Override;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use Psl\Option;
use RuntimeException;

/**
 * @implements Script<object>
 *
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 * @mago-expect lint:too-many-methods
 */
abstract readonly class BotScript implements Script
{
    private const array FIGHT_EVENTS = [
        MonstersDiedEvent::class,
        MonsterRewardedEvent::class,
        MonsterRespawnedEvent::class,
        PlayerDiedEvent::class,
        LeveledUpEvent::class,
    ];

    /** @var Closure(): Timestamp */
    private Closure $clock;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(
        protected Character $me,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? Timestamp::monotonic(...);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    abstract protected function play(): Generator;

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    #[Override]
    final public function run(): Generator
    {
        return $this->play();
    }

    /**
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function join(MapName $map): Generator
    {
        if ($this->me->position()->map->value === $map->value) {
            return;
        }

        /** @var Event|TimedOut $joined */
        $joined = yield $this->send(new JoinMapCommand($this->me->name(), $map, Option\none()))->awaits(
            Duration::seconds(15),
            MapJoins::class,
            MapRefusals::class,
        );

        if (!MapJoins::contains($joined)) {
            throw new RuntimeException("Could not join {$map->value}: " . self::why($joined));
        }
    }

    protected function send(Command $command): Send
    {
        return new Send($command);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    protected function jump(Cell $cell, Pad $pad): Generator
    {
        yield $this->send(new MoveToCellCommand($this->me->position()->area, $cell, $pad));
        yield new Pause(Duration::seconds(1));
    }

    /**
     * @param Closure(): bool $until
     *
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function kill(Cell $cell, Pad $pad, Attack $attack, Closure $until): Generator
    {
        yield from $this->jump($cell, $pad);
        $targets = $this->targets($cell, $attack);

        $dead = [];
        $ready = [];
        while (!$until()) {
            if (!$this->me->alive()) {
                yield from $this->resurrect($cell, $pad);
                $dead = [];

                continue;
            }

            $target = self::firstAlive($targets, $dead);
            $now = ($this->clock)();
            $skill = $target === null ? null : $this->readySkill($ready, $now);
            if ($target !== null && $skill !== null) {
                yield $this->send($attack->at($target)->with($skill->slot));
                $ready[$skill->slot->name] = $now->plus($skill->cooldown);
                $ready['next'] = $now->plus(Duration::seconds(1));
            }

            $answer = yield new Await(self::FIGHT_EVENTS, self::nextAction($ready, $now));
            $dead = self::seen($answer, $dead);
        }
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    private function resurrect(Cell $cell, Pad $pad): Generator
    {
        yield new Pause(Duration::seconds(10));
        yield $this->send(new ResurrectCommand($this->me->position()->area, $this->me->socket()))->awaits(
            Duration::seconds(15),
            ResurrectedEvent::class,
        );
        yield from $this->jump($cell, $pad);
    }

    /**
     * @throws RuntimeException
     *
     * @return non-empty-list<Targetable>
     */
    private function targets(Cell $cell, Attack $attack): array
    {
        $aimed = $attack->target();
        if (!$aimed instanceof MonsterSpawnId) {
            return [$aimed];
        }

        $placed = $this->me->map()->monstersIn($cell);
        $species = null;
        foreach ($placed as $placement) {
            if ($placement->id->value !== $aimed->value) {
                continue;
            }

            $species = $placement->monster->value;
        }

        if ($species === null) {
            throw new RuntimeException("Monster {$aimed->value} does not stand in {$cell->value}.");
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
    private function readySkill(array $ready, Timestamp $now): ?Skill
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
    private static function nextAction(array $ready, Timestamp $now): Duration
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

    private static function why(mixed $answer): string
    {
        return match (true) {
            $answer instanceof Event => $answer->summary(),
            $answer instanceof TimedOut => 'no answer',
            default => 'the server refused',
        };
    }
}
