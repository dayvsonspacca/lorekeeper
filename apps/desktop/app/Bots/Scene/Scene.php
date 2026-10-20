<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Client\Character;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Map\MapMonster;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type Skill = array{slot: string, cost: int, restores?: int, cooldown: float, targets: int, target: string, unlocked: bool}
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
        $player = $me->player();
        $class = $player->class;
        $rank = $class->rank();

        return new self(
            [
                'name' => $player->name->value,
                'level' => $player->progress->level->value,
                'experience' => $player->progress->experience->current->value,
                'maxExperience' => $player->progress->experience->maximum->value,
                'gold' => $player->wallet->gold->value,
                'member' => $player->tags->has(PlayerTag::Member),
                'health' => $player->vitals->health->current->value,
                'maxHealth' => $player->vitals->health->maximum->value,
                'mana' => $player->vitals->mana->current->value,
                'maxMana' => $player->vitals->mana->maximum->value,
                'class' => $class->class->name->value,
                'category' => strtolower($class->class->category->name),
                'rank' => $rank->value,
                'points' => $class->points->value,
                'rankFrom' => $rank->minimumPoints(),
                'rankTo' => $rank->value < 10 ? new Rank($rank->value + 1)->minimumPoints() : null,
                'state' => strtolower($me->state()->name),
                'away' => $me->away(),
                'map' => $me->location()->map->name->value,
                'room' => $me->location()->room->value,
                'items' => count($player->inventory->permanent->items) + count($player->inventory->temporary->items),
                'skills' => array_map(static fn(PlayerSkill $skill): array => [
                    'slot' => $skill->skill->slot->name,
                    'cost' => $skill->skill->cost instanceof ManaCost ? $skill->skill->cost->mana->value : 0,
                    'restores' => $skill->skill->cost instanceof ManaRestore ? $skill->skill->cost->mana->value : 0,
                    'cooldown' => round($skill->skill->cooldown->getTotalSeconds(), precision: 1),
                    'targets' => $skill->skill->targets->value,
                    'target' => strtolower($skill->skill->target->name),
                    'unlocked' => $skill->unlocked,
                ], $class->skills),
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
            Backpack::of($player->inventory),
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
                ])),
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
