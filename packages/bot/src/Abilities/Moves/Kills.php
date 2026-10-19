<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Moves;

use Closure;
use Generator;
use InvalidArgumentException;
use Lorekeeper\Bot\Abilities\Fight\ClassRules;
use Lorekeeper\Bot\Abilities\Fight\Cooldowns;
use Lorekeeper\Bot\Abilities\Fight\Targets;
use Lorekeeper\Client\Command\Combat\Attack;
use Lorekeeper\Client\Command\Combat\AttackMonsterCommand;
use Lorekeeper\Client\Command\Combat\ResurrectCommand;
use Lorekeeper\Client\Command\Combat\SupportPlayerCommand;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use RuntimeException;

trait Kills
{
    use Jumps;

    abstract protected function now(): Timestamp;

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
        $targets = Targets::in($this->me()->location()->map, $spot->cell, $attack);
        $cooldowns = new Cooldowns();

        while (!$until()) {
            if (!$this->me()->player()->vitals->alive()) {
                yield from $this->resurrect($spot);
                $targets = $targets->revived();

                continue;
            }

            $target = $targets->next();
            $now = $this->now();
            $skill = $target === null
                ? null
                : $cooldowns->ready(
                    $this->unlocked(),
                    $this->me()->player()->vitals,
                    ClassRules::of($this->me()->player()->class->class->name),
                    $now,
                );
            if ($target !== null && $skill !== null) {
                yield $this->send($this->cast($skill, $attack->at($target)));
                $cooldowns = $cooldowns->used($skill, $now);
            }

            $targets = $targets->heard(
                yield new Await([
                    MonstersDiedEvent::class,
                    MonsterRewardedEvent::class,
                    MonsterRespawnedEvent::class,
                    PlayerDiedEvent::class,
                    LeveledUpEvent::class,
                ], $cooldowns->wait($now)),
            );
        }
    }

    /** @throws InvalidArgumentException */
    private function cast(Skill $skill, Attack $attack): Command
    {
        if ($skill->target === SkillTarget::Enemy) {
            return $attack->with($skill->slot);
        }

        return new SupportPlayerCommand($this->me()->location()->area, $skill->slot, $this->me()->socketId());
    }

    /** @throws RuntimeException */
    protected function attackIn(MapCell $cell): AttackMonsterCommand
    {
        foreach ($this->me()->location()->map->monsters as $monster) {
            if ($monster->cell->value === $cell->value) {
                return new AttackMonsterCommand($this->me()->location()->area, SkillSlot::AutoAttack, $monster->id);
            }
        }

        throw new RuntimeException("There are no monsters in {$cell->value}.");
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    private function resurrect(MapSpot $spot): Generator
    {
        yield new Pause(Duration::seconds(10));
        yield $this->send(new ResurrectCommand($this->me()->location()->area, $this->me()->socketId()))->awaits(
            Duration::seconds(15),
            ResurrectedEvent::class,
        );
        yield from $this->jump($spot);
    }

    /** @return list<Skill> */
    private function unlocked(): array
    {
        return array_values(array_map(
            static fn(PlayerSkill $held): Skill => $held->skill,
            array_filter($this->me()->player()->class->skills, static fn(PlayerSkill $held): bool => $held->unlocked),
        ));
    }
}
