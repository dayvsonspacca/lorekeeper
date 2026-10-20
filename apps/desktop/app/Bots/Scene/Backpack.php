<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Psl\Type;

/**
 * @psalm-type Held = array{id: int, name: string, kind: string, type: string, quantity: int, member: bool, level?: int, equipped?: bool, enhancement?: ?array{type: string, rarity: string, level: int, effect: ?array{name: string, description: string}}, variance?: int}
 */
final readonly class Backpack
{
    private const array WORN_IN_ORDER = ['class', 'weapon', 'armor', 'helm', 'cape', 'pet', 'necklace', 'ground'];

    /**
     * @param list<Held> $kept
     * @param list<Held> $temporary
     */
    public function __construct(
        public array $kept = [],
        public array $temporary = [],
    ) {}

    public static function of(PlayerInventory $inventory): self
    {
        return new self(self::held($inventory->permanent), self::held($inventory->temporary));
    }

    /** @param array{kept: list<Held>, temporary: list<Held>} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['kept'], $data['temporary']);
    }

    /** @return array{kept: list<Held>, temporary: list<Held>} */
    public function toArray(): array
    {
        return ['kept' => $this->kept, 'temporary' => $this->temporary];
    }

    public function count(): int
    {
        return count($this->kept) + count($this->temporary);
    }

    /** @return list<Held> */
    public function equipped(): array
    {
        $worn = array_values(array_filter($this->kept, static fn(array $held): bool => $held['equipped'] ?? false));
        usort(
            $worn,
            static fn(array $a, array $b): int => (
                array_search($a['kind'], self::WORN_IN_ORDER, strict: true) <=> array_search(
                    $b['kind'],
                    self::WORN_IN_ORDER,
                    strict: true,
                )
            ),
        );

        return $worn;
    }

    /** @return array<string, int> */
    public function kinds(): array
    {
        $kinds = array_count_values(array_column($this->kept, 'kind'));
        ksort($kinds);

        return $kinds;
    }

    /** @return Type\TypeInterface<array{kept: list<Held>, temporary: list<Held>}> */
    public static function type(): Type\TypeInterface
    {
        $held = Type\vec(Type\shape([
            'id' => Type\int(),
            'name' => Type\string(),
            'kind' => Type\string(),
            'type' => Type\string(),
            'quantity' => Type\int(),
            'member' => Type\bool(),
            'level' => Type\optional(Type\int()),
            'equipped' => Type\optional(Type\bool()),
            'enhancement' => Type\optional(Type\nullable(Type\shape([
                'type' => Type\string(),
                'rarity' => Type\string(),
                'level' => Type\int(),
                'effect' => Type\nullable(Type\shape([
                    'name' => Type\string(),
                    'description' => Type\string(),
                ])),
            ]))),
            'variance' => Type\optional(Type\int()),
        ]));

        return Type\shape(['kept' => $held, 'temporary' => $held]);
    }

    /** @return list<Held> */
    private static function held(PlayerItems $items): array
    {
        $held = array_values(array_map(static fn(PlayerItem $held): array => [
            'id' => $held->item->id->value,
            'name' => $held->item->name->value,
            'kind' => Kind::of($held->item),
            'type' => Kind::label($held->item),
            'quantity' => $held->quantity->value,
            'member' => $held->item->tags->has(ItemTag::MemberOnly),
            'level' => $held->item->level->value,
            'equipped' => $held->equipped,
            'enhancement' => Enhanced::of($held->item),
            ...($held->item instanceof Weapon ? ['variance' => $held->item->damage->variance->percent] : []),
        ], $items->items->toArray()));

        usort($held, static fn(array $a, array $b): int => [$a['kind'], $a['name']] <=> [$b['kind'], $b['name']]);

        return $held;
    }
}
