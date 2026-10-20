<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Psl\Type;

/**
 * @psalm-type Held = array{id: int, name: string, kind: string, type: string, quantity: int, member: bool}
 */
final readonly class Backpack
{
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
        ]));

        return Type\shape(['kept' => $held, 'temporary' => $held]);
    }

    /** @return list<Held> */
    private static function held(PlayerItems $items): array
    {
        $held = array_values(array_map(static fn(PlayerItem $held): array => [
            'id' => $held->item->id->value,
            'name' => $held->item->name->value,
            'kind' => self::kind($held->item),
            'type' => self::label($held->item),
            'quantity' => $held->quantity->value,
            'member' => $held->item->tags->has(ItemTag::MemberOnly),
        ], $items->items->toArray()));

        usort($held, static fn(array $a, array $b): int => [$a['kind'], $a['name']] <=> [$b['kind'], $b['name']]);

        return $held;
    }

    private static function kind(Item $item): string
    {
        return match (true) {
            $item instanceof ClassItem => 'class',
            $item instanceof Armor => 'armor',
            $item instanceof Helm => 'helm',
            $item instanceof Cape => 'cape',
            $item instanceof Weapon => 'weapon',
            $item instanceof Pet => 'pet',
            $item instanceof Necklace => 'necklace',
            $item instanceof GroundItem => 'ground',
            $item instanceof HouseItem => 'house',
            $item instanceof Boost => 'boost',
            $item instanceof EnhancementItem => 'enhancement',
            default => 'resource',
        };
    }

    private static function label(Item $item): string
    {
        return match (true) {
            $item instanceof Weapon => $item->type->name,
            $item instanceof HouseItem => "{$item->type->name} item",
            default => ucfirst(self::kind($item)),
        };
    }
}
