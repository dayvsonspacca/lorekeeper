<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\Type;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type as Coerce;
use Psl\Type\Exception\CoercionException;

/** @mago-expect lint:cyclomatic-complexity */
final readonly class PlayerInventoryLoadedEvent implements Event
{
    use NamedAfterClass;

    /** @param list<InventoryItem> $items */
    public function __construct(
        public array $items,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'loadInventoryBig') {
            return Option\none();
        }

        try {
            $items = Coerce\vec(Coerce\shape([
                'ItemID' => Coerce\positive_int(),
                'sName' => Coerce\non_empty_string(),
                'sType' => Coerce\string(),
                'sES' => Coerce\string(),
                'sDesc' => Coerce\optional(Coerce\string()),
                'iCost' => Coerce\optional(Coerce\uint()),
                'bCoins' => Coerce\optional(Coerce\bool()),
                'sFile' => Coerce\optional(Coerce\nullable(Coerce\string())),
                'sLink' => Coerce\optional(Coerce\string()),
                'bUpg' => Coerce\optional(Coerce\bool()),
            ], allowUnknownFields: true))
                ->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $inventory = [];
        foreach ($items as $item) {
            $description = trim($item['sDesc'] ?? '');
            $cost = $item['iCost'] ?? 0;
            $file = $item['sFile'] ?? '';
            $link = $item['sLink'] ?? '';
            $hasFile = str_ends_with($file, '.swf');

            $inventory[] = new InventoryItem(
                new ItemId($item['ItemID']),
                new ItemName($item['sName']),
                self::detail($item['sType']),
                $description === '' ? Option\none() : Option\some(new ItemDescription($description)),
                $cost === 0
                    ? Option\none()
                    : Option\some(
                        new ItemPrice($item['bCoins'] ?? false ? new AdventureCoins($cost) : new Gold($cost)),
                    ),
                $hasFile ? Option\some(new Gamefile($file)) : Option\none(),
                $hasFile && $link !== '' ? Option\some(new Linkage($link)) : Option\none(),
                self::slot($item['sES']),
                $item['bUpg'] ?? false,
            );
        }

        return Option\some(new static($inventory));
    }

    /** @throws MalformedEventException */
    private static function detail(string $type): Type\Equipment|Type\Weapon|Type\House|Type\Misc
    {
        return match ($type) {
            'Armor' => Type\Equipment::Armor,
            'Cape' => Type\Equipment::Cape,
            'Class' => Type\Equipment::CharacterClass,
            'Misc' => Type\Equipment::Ground,
            'Helm' => Type\Equipment::Helm,
            'Necklace' => Type\Equipment::Necklace,
            'Pet' => Type\Equipment::Pet,
            'Axe' => Type\Weapon::Axe,
            'Bow' => Type\Weapon::Bow,
            'Dagger' => Type\Weapon::Dagger,
            'Gauntlet' => Type\Weapon::Gauntlet,
            'Gun' => Type\Weapon::Gun,
            'HandGun' => Type\Weapon::HandGun,
            'Mace' => Type\Weapon::Mace,
            'Polearm' => Type\Weapon::Polearm,
            'Rifle' => Type\Weapon::Rifle,
            'Staff' => Type\Weapon::Staff,
            'Sword' => Type\Weapon::Sword,
            'Wand' => Type\Weapon::Wand,
            'Whip' => Type\Weapon::Whip,
            'Floor Item' => Type\House::Floor,
            'House' => Type\House::House,
            'Wall Item' => Type\House::Wall,
            'ServerUse' => Type\Misc::Boost,
            'Enhancement' => Type\Misc::Enhancement,
            'Item', 'Note', 'Quest Item', 'Resource' => Type\Misc::Generic,
            default => throw new MalformedEventException(static::class, "unknown item type '{$type}'"),
        };
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<EquipmentSlot>
     */
    private static function slot(string $code): Option\Option
    {
        return match ($code) {
            'None' => Option\none(),
            'Weapon' => Option\some(EquipmentSlot::Weapon),
            'ar', 'co' => Option\some(EquipmentSlot::Armor),
            'he' => Option\some(EquipmentSlot::Helm),
            'ba' => Option\some(EquipmentSlot::Cape),
            'pe' => Option\some(EquipmentSlot::Pet),
            'mi' => Option\some(EquipmentSlot::Ground),
            'am' => Option\some(EquipmentSlot::Necklace),
            default => throw new MalformedEventException(static::class, "unknown equipment slot '{$code}'"),
        };
    }

    #[Override]
    public function summary(): string
    {
        return 'Inventory loaded: ' . count($this->items) . ' items';
    }
}
