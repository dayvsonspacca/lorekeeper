<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Item\Helper;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon;
use Lorekeeper\Core\Item\Equipment\WeaponType;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\House\HouseItemType;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Enhancement;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;

final readonly class ItemKinds
{
    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     */
    public static function read(ItemId $id, ItemName $name, string $type, string $event): Item
    {
        return match ($type) {
            'Class' => new ClassItem($id, $name),
            'Armor' => new Armor($id, $name),
            'Helm' => new Helm($id, $name),
            'Cape' => new Cape($id, $name),
            'Pet' => new Pet($id, $name),
            'Necklace' => new Necklace($id, $name),
            'Misc' => new GroundItem($id, $name),
            'Axe' => new Weapon($id, $name, WeaponType::Axe),
            'Bow' => new Weapon($id, $name, WeaponType::Bow),
            'Dagger' => new Weapon($id, $name, WeaponType::Dagger),
            'Gauntlet' => new Weapon($id, $name, WeaponType::Gauntlet),
            'Gun' => new Weapon($id, $name, WeaponType::Gun),
            'HandGun' => new Weapon($id, $name, WeaponType::HandGun),
            'Mace' => new Weapon($id, $name, WeaponType::Mace),
            'Polearm' => new Weapon($id, $name, WeaponType::Polearm),
            'Rifle' => new Weapon($id, $name, WeaponType::Rifle),
            'Staff' => new Weapon($id, $name, WeaponType::Staff),
            'Sword' => new Weapon($id, $name, WeaponType::Sword),
            'Wand' => new Weapon($id, $name, WeaponType::Wand),
            'Whip' => new Weapon($id, $name, WeaponType::Whip),
            'Floor Item' => new HouseItem($id, $name, HouseItemType::Floor),
            'Wall Item' => new HouseItem($id, $name, HouseItemType::Wall),
            'House' => new HouseItem($id, $name, HouseItemType::House),
            'ServerUse' => new Boost($id, $name),
            'Enhancement' => new Enhancement($id, $name),
            'Item', 'Note', 'Quest Item', 'Resource' => new Resource($id, $name),
            default => throw new MalformedEventException($event, "unknown item type '{$type}'"),
        };
    }
}
