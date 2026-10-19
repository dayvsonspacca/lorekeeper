<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Character;
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
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
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
abstract class BotScript implements Script
{
    private const array FIGHT_EVENTS = [
        MonstersDiedEvent::class,
        MonsterRewardedEvent::class,
        MonsterRespawnedEvent::class,
        PlayerDiedEvent::class,
        LeveledUpEvent::class,
    ];

    protected Character $me;

    /** @var Closure(): Timestamp */
    private readonly Closure $clock;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(?Closure $clock = null)
    {
        $this->clock = $clock ?? Timestamp::monotonic(...);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    abstract protected function rules(): Generator;

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    #[Override]
    final public function run(Character $me): Generator
    {
        $this->me = $me;

        return $this->rules();
    }

    /**
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function join(MapName $map): Generator
    {
        if ($this->me->location()->map->name->value === $map->value) {
            return;
        }

        /** @var Event|TimedOut $joined */
        $joined = yield $this->send(new JoinMapCommand($this->me->player()->name, $map, Option\none()))->awaits(
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
    protected function jump(MapSpot $spot): Generator
    {
        yield $this->send(new MoveToCellCommand($this->me->location()->area, $spot));
        yield new Pause(Duration::seconds(1));
    }

    /**
     * @param Closure(): bool $until
     *
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function kill(MapSpot $spot, Attack $attack, Closure $until): Generator
    {
        yield from $this->jump($spot);
        $targets = $this->targets($spot->cell, $attack);

        $dead = [];
        $ready = [];
        while (!$until()) {
            if (!$this->me->player()->vitals->alive()) {
                yield from $this->resurrect($spot);
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
    private function resurrect(MapSpot $spot): Generator
    {
        yield new Pause(Duration::seconds(10));
        yield $this->send(new ResurrectCommand($this->me->location()->area, $this->me->socketId()))->awaits(
            Duration::seconds(15),
            ResurrectedEvent::class,
        );
        yield from $this->jump($spot);
    }

    /**
     * @throws RuntimeException
     *
     * @return non-empty-list<Targetable>
     */
    private function targets(MapCell $cell, Attack $attack): array
    {
        $aimed = $attack->target();
        if (!$aimed instanceof MonsterSpawnId) {
            return [$aimed];
        }

        $here = array_values(array_filter(
            $this->me->location()->map->monsters,
            static fn(MapMonster $monster): bool => $monster->cell->value === $cell->value,
        ));

        $kind = null;
        foreach ($here as $monster) {
            if ($monster->id->value !== $aimed->value) {
                continue;
            }

            $kind = $monster->monster;
        }

        if ($kind === null) {
            throw new RuntimeException("Monster {$aimed->value} does not stand in {$cell->value}.");
        }

        $same = [];
        foreach ($here as $monster) {
            if ($monster->monster !== $kind || $monster->id->value === $aimed->value) {
                continue;
            }

            $same[] = $monster->id;
        }

        return [$aimed, ...$same];
    }

    /** @param array<string, Timestamp> $ready */
    private function readySkill(array $ready, Timestamp $now): ?Skill
    {
        if (array_key_exists('next', $ready) && $ready['next']->after($now)) {
            return null;
        }

        $usable = array_filter(
            array_map(
                static fn(PlayerSkill $held): Skill => $held->skill,
                array_filter($this->me->player()->skills, static fn(PlayerSkill $held): bool => $held->unlocked),
            ),
            fn(Skill $skill): bool => (
                $skill->target === SkillTarget::Enemy
                && !(array_key_exists($skill->slot->name, $ready) && $ready[$skill->slot->name]->after($now))
                && $skill->cost->value <= $this->me
                    ->player()
                    ->vitals
                    ->mana
                    ->current
                    ->value
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
