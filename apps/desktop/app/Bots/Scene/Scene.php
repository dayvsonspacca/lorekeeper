<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Client\Character;
use Lorekeeper\Core\Game\Rank\Rank;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Damage\SkillDamage;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillDescription;
use Lorekeeper\Core\Player\Hero\Class\HeroSkill;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\World\Map\MapMonster;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type Skill = array{slot: string, cost: int, restores?: int, cooldown: float, targets: int, target: string, unlocked: bool, name?: string, description?: ?string, damage?: ?float, source?: ?string}
 * @psalm-type Passive = array{name: string, description: string}
 * @psalm-type Hero = array{
 *     name: string,
 *     level: int,
 *     experience?: int,
 *     maxExperience?: int,
 *     gold?: int,
 *     member?: bool,
 *     health: int,
 *     maxHealth: int,
 *     mana: int,
 *     maxMana: int,
 *     class: string,
 *     category: string,
 *     rank: int,
 *     points: int,
 *     rankFrom: int,
 *     rankTo: ?int,
 *     state: string,
 *     away: bool,
 *     map: string,
 *     room?: int,
 *     items: int,
 *     skills: list<Skill>,
 *     passives?: list<Passive>,
 *     stats?: list<array{group: string, name: string, shown: string, about: string}>,
 * }
 * @psalm-type Monster = array{
 *     id: int,
 *     name: string,
 *     level: int,
 *     race: string,
 *     cell: string,
 *     health: int,
 *     maxHealth: int,
 *     state: string,
 *     struck: ?int,
 * }
 */
final readonly class Scene
{
    public const int ENGAGED_SECONDS = 8;

    /**
     * @param Hero $hero
     * @param list<Monster> $monsters
     * @param list<string> $players
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public array $hero,
        public array $monsters,
        public array $players,
        public Pulse $pulse,
        public Feed $feed,
        public int $at,
        public Backpack $backpack = new Backpack(),
    ) {}

    public static function capture(Character $me, Room $room, Pulse $pulse, Feed $feed, int $now): self
    {
        $hero = $me->hero();
        $class = $hero->class;
        $rank = $class->rank();

        return new self(
            [
                'name' => $hero->name->value,
                'level' => $hero->level->value,
                'experience' => $hero->progression->level->experience->current->value,
                'maxExperience' => $hero->progression->level->experience->maximum->value,
                'gold' => $hero->inventory->wallet->gold->value,
                'member' => $hero->tags->has(PlayerTag::Member),
                'health' => $hero->vitals->health->current->value,
                'maxHealth' => $hero->vitals->health->maximum->value,
                'mana' => $hero->vitals->mana->current->value,
                'maxMana' => $hero->vitals->mana->maximum->value,
                'class' => $class->class->name->value,
                'category' => strtolower($class->class->category->name),
                'rank' => $rank->value,
                'points' => $class->points->value,
                'rankFrom' => $rank->start(),
                'rankTo' => $rank->value < 10 ? new Rank($rank->value + 1)->start() : null,
                'state' => strtolower($me->state()->name),
                'away' => $me->away(),
                'map' => $me->location()->map->name->value,
                'room' => $me->location()->map->room->value,
                'items' => count($hero->inventory->permanent) + count($hero->inventory->temporary),
                'skills' => array_map(static fn(HeroSkill $skill): array => [
                    'slot' => $skill->skill->slot->name,
                    'cost' => $skill->skill->cost instanceof ManaCost ? $skill->skill->cost->mana->value : 0,
                    'restores' => $skill->skill->cost instanceof ManaRestore ? $skill->skill->cost->mana->value : 0,
                    'cooldown' => round($skill->skill->cooldown->getTotalSeconds(), precision: 1),
                    'targets' => $skill->skill->targets->value,
                    'target' => strtolower($skill->skill->target->name),
                    'unlocked' => $skill->unlocked,
                    'name' => $skill->skill->name->value,
                    'description' => $skill
                        ->skill
                        ->description
                        ->map(static fn(SkillDescription $description): string => $description->value)
                        ->unwrapOr(null),
                    'damage' => $skill
                        ->skill
                        ->damage
                        ->map(static fn(SkillDamage $damage): float => $damage->multiplier->value)
                        ->unwrapOr(null),
                    'source' => $skill
                        ->skill
                        ->damage
                        ->map(static fn(SkillDamage $damage): string => strtolower($damage->source->name))
                        ->unwrapOr(null),
                ], $class->skills),
                'passives' => array_map(static fn(ClassPassive $passive): array => [
                    'name' => $passive->name->value,
                    'description' => $passive->description->value,
                ], $class->class->passives),
                'stats' => StatSheet::of($hero->attributes->stats),
            ],
            array_values(array_map(static fn(MapMonster $monster): array => [
                'id' => $monster->id->value,
                'name' => $monster->monster->name->value,
                'level' => $monster->monster->level->value,
                'race' => strtolower($monster->monster->race->name),
                'cell' => $monster->cell->value,
                'health' => $monster->vitals->health->current->value,
                'maxHealth' => $monster->vitals->health->maximum->value,
                'state' => strtolower($monster->state->name),
                'struck' => $room->struck[$monster->id->value] ?? null,
            ], $room->monsters)),
            $room->players,
            $pulse,
            $feed,
            $now,
            Backpack::of($hero->inventory),
        );
    }

    public static function fromArray(mixed $data): ?self
    {
        try {
            $scene = self::type()->coerce($data);
        } catch (CoercionException) {
            return null;
        }

        return new self(
            $scene['hero'],
            $scene['monsters'],
            $scene['players'],
            new Pulse($scene['pulse']),
            new Feed($scene['feed']),
            $scene['at'],
            Backpack::fromArray($scene['backpack'] ?? ['kept' => [], 'temporary' => []]),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'hero' => $this->hero,
            'monsters' => $this->monsters,
            'players' => $this->players,
            'pulse' => $this->pulse->minutes,
            'feed' => $this->feed->lines,
            'at' => $this->at,
            'backpack' => $this->backpack->toArray(),
        ];
    }

    /** @return list<Monster> */
    public function engaged(int $now): array
    {
        return array_values(array_filter(
            $this->monsters,
            static fn(array $monster): bool => (
                $monster['struck'] !== null
                && ($now - $monster['struck'])
                <= self::ENGAGED_SECONDS
            ),
        ));
    }

    /** @return array<string, list<Monster>> */
    public function cells(int $now): array
    {
        $engaged = array_column($this->engaged($now), 'cell');
        $cells = [];
        foreach ($this->monsters as $monster) {
            $cells[$monster['cell']][] = $monster;
        }

        uksort(
            $cells,
            static fn(string $a, string $b): int => (
                [
                    !in_array($a, $engaged, strict: true),
                    $a,
                ] <=> [!in_array($b, $engaged, strict: true), $b]
            ),
        );

        return $cells;
    }

    public function alive(): int
    {
        return count(array_filter($this->monsters, static fn(array $monster): bool => $monster['state'] !== 'dead'));
    }

    public function place(): string
    {
        $room = $this->hero['room'] ?? null;

        return $room === null ? $this->hero['map'] : "{$this->hero['map']}-{$room}";
    }

    public function rankPercent(): int
    {
        $to = $this->hero['rankTo'];
        if ($to === null) {
            return 100;
        }

        $span = max(1, $to - $this->hero['rankFrom']);

        return (int) max(0, min(100, round((($this->hero['points'] - $this->hero['rankFrom']) * 100) / $span)));
    }

    /**
     * @return Type\TypeInterface<array{
     *     hero: Hero,
     *     monsters: list<Monster>,
     *     players: list<string>,
     *     pulse: array<int, array{int, int}>,
     *     feed: list<array{at: int, kind: string, text: string, detail: ?string}>,
     *     at: int,
     *     backpack?: array{
     *         kept: list<array{id: int, name: string, kind: string, type: string, quantity: int, member: bool}>,
     *         temporary: list<array{id: int, name: string, kind: string, type: string, quantity: int, member: bool}>,
     *     },
     * }>
     */
    private static function type(): Type\TypeInterface
    {
        return Type\shape([
            'hero' => Type\shape([
                'name' => Type\string(),
                'level' => Type\int(),
                'experience' => Type\optional(Type\int()),
                'maxExperience' => Type\optional(Type\int()),
                'gold' => Type\optional(Type\int()),
                'member' => Type\optional(Type\bool()),
                'health' => Type\int(),
                'maxHealth' => Type\int(),
                'mana' => Type\int(),
                'maxMana' => Type\int(),
                'class' => Type\string(),
                'category' => Type\string(),
                'rank' => Type\int(),
                'points' => Type\int(),
                'rankFrom' => Type\int(),
                'rankTo' => Type\nullable(Type\int()),
                'state' => Type\string(),
                'away' => Type\bool(),
                'map' => Type\string(),
                'room' => Type\optional(Type\int()),
                'items' => Type\int(),
                'skills' => Type\vec(Type\shape([
                    'slot' => Type\string(),
                    'cost' => Type\int(),
                    'restores' => Type\optional(Type\int()),
                    'cooldown' => Type\float(),
                    'targets' => Type\int(),
                    'target' => Type\string(),
                    'unlocked' => Type\bool(),
                    'name' => Type\optional(Type\string()),
                    'description' => Type\optional(Type\nullable(Type\string())),
                    'damage' => Type\optional(Type\nullable(Type\float())),
                    'source' => Type\optional(Type\nullable(Type\string())),
                ])),
                'passives' => Type\optional(Type\vec(Type\shape([
                    'name' => Type\string(),
                    'description' => Type\string(),
                ]))),
                'stats' => Type\optional(Type\vec(Type\shape([
                    'group' => Type\string(),
                    'name' => Type\string(),
                    'shown' => Type\string(),
                    'about' => Type\string(),
                ]))),
            ]),
            'monsters' => Type\vec(Type\shape([
                'id' => Type\int(),
                'name' => Type\string(),
                'level' => Type\int(),
                'race' => Type\string(),
                'cell' => Type\string(),
                'health' => Type\int(),
                'maxHealth' => Type\int(),
                'state' => Type\string(),
                'struck' => Type\nullable(Type\int()),
            ])),
            'players' => Type\vec(Type\string()),
            'pulse' => Type\dict(Type\int(), Type\shape([Type\int(), Type\int()])),
            'feed' => Type\vec(Type\shape([
                'at' => Type\int(),
                'kind' => Type\string(),
                'text' => Type\string(),
                'detail' => Type\nullable(Type\string()),
            ])),
            'at' => Type\int(),
            'backpack' => Type\optional(Backpack::type()),
        ]);
    }
}
