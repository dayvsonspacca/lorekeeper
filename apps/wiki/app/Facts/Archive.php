<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts;

use Illuminate\Database\Eloquent\Model;
use Lorekeeper\Apps\Wiki\Models;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\House\HouseItemType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;

final readonly class Archive
{
    /** @var array<class-string, class-string<Model>> */
    private const array KINDS = [
        Armor::class => Models\Armor::class,
        Weapon::class => Models\Weapon::class,
        Helm::class => Models\Helm::class,
        Cape::class => Models\Cape::class,
        Pet::class => Models\Pet::class,
        GroundItem::class => Models\GroundItem::class,
        ClassItem::class => Models\ClassItem::class,
        HouseItem::class => Models\HouseItem::class,
        Boost::class => Models\Boost::class,
        Resource::class => Models\Resource::class,
    ];

    private function __construct(
        private Ledger $ledger,
    ) {}

    /** @param list<Fact<object, object>> $facts */
    public static function record(array $facts): void
    {
        if ($facts === []) {
            return;
        }

        $archive = new self(new Ledger());
        foreach ($facts as $fact) {
            $archive->learn($fact);
        }

        $archive->ledger->write();
    }

    /** @param Fact<object, object> $fact */
    private function learn(Fact $fact): void
    {
        if ($fact->subject instanceof ItemId) {
            $this->item($fact->subject->value, $fact->value);
        }
    }

    private function item(int $id, object $value): void
    {
        match (true) {
            $value instanceof ItemName => $this->ledger->name($id, $value->value),
            $value instanceof ItemTag => $this->ledger->tag($id, $value),
            $value instanceof Kind && array_key_exists($value->item, self::KINDS) => $this->ledger->kind(
                self::KINDS[$value->item],
                $id,
            ),
            $value instanceof WeaponType => $this->ledger->kind(Models\Weapon::class, $id, ['type' => $value->name]),
            $value instanceof DamageVariance => $this->ledger->kind(Models\Weapon::class, $id, [
                'damage_variance' => $value->percent,
            ]),
            $value instanceof HouseItemType => $this->ledger->kind(Models\HouseItem::class, $id, [
                'type' => $value->name,
            ]),
            $value instanceof Quantity => $this->ledger->stack($id, $value->value),
            default => $this->ledger->item($id),
        };
    }
}
