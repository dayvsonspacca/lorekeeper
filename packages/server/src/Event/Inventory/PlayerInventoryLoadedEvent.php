<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use InvalidArgumentException;
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
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Enhancement;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerInventoryLoadedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public PlayerInventory $inventory,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'loadInventoryBig') {
            return Option\none();
        }

        try {
            $items = Type\vec(Type\shape([
                'ItemID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'sType' => Type\string(),
                'iQty' => Type\optional(Type\positive_int()),
                'bUpg' => Type\optional(Type\bool()),
            ], allowUnknownFields: true))
                ->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $held = [];
        foreach ($items as $entry) {
            $item = self::item(new ItemId($entry['ItemID']), new ItemName($entry['sName']), $entry['sType']);
            if ($entry['bUpg'] ?? false) {
                $item->tags->add(ItemTag::MemberOnly);
            }

            $held[] = new PlayerItem(
                $item,
                $item instanceof ClassItem ? new Quantity(1) : new Quantity($entry['iQty'] ?? 1),
            );
        }

        try {
            return Option\some(new static(new PlayerInventory($held)));
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, 'an item is in the inventory more than once', $exception);
        }
    }

    #[Override]
    public function summary(): string
    {
        return 'Inventory loaded: ' . count($this->inventory->items) . ' items';
    }

    /** @throws MalformedEventException */
    private static function item(ItemId $id, ItemName $name, string $type): Item
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
            default => throw new MalformedEventException(static::class, "unknown item type '{$type}'"),
        };
    }
}
