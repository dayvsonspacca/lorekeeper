<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Drops;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

/** @mago-expect lint:too-many-methods */
final class InventoryTrackingTest extends TestCase
{
    #[Test]
    public function quest_items_pile_up_as_temporary_and_leave_when_turned_in(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30));

        $inventory = InventoryTracking::follow($inventory, new QuestItemsReceivedEvent([self::ore(1)]), self::me());
        $inventory = InventoryTracking::follow($inventory, new QuestItemsReceivedEvent([self::ore(2)]), self::me());
        static::assertSame([3, 3], [
            $inventory->quantity(new ItemId(42_658)),
            $inventory->temporary->find(new ItemId(42_658))->unwrap()->quantity->value,
        ]);

        $inventory = InventoryTracking::follow($inventory, self::turnedIn(42_658, 2), self::me());
        static::assertSame(1, $inventory->quantity(new ItemId(42_658)));

        $inventory = InventoryTracking::follow($inventory, self::turnedIn(42_658, 1), self::me());
        static::assertTrue($inventory->find(new ItemId(42_658))->isNone());
        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_quest_can_take_kept_items_too(): void
    {
        $inventory = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30)),
            self::turnedIn(42_657, 3),
            self::me(),
        );

        static::assertTrue($inventory->find(new ItemId(42_657))->isNone());
    }

    #[Test]
    public function loading_the_inventory_keeps_what_is_held_for_now(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([]), new Quantity(30), new PlayerItems([self::ore(1)]));

        $inventory = InventoryTracking::follow(
            $inventory,
            new InventoryLoadedEvent(new PlayerItems([self::token(5)])),
            self::me(),
        );

        static::assertSame([5, 1], [
            $inventory->quantity(new ItemId(42_657)),
            $inventory->quantity(new ItemId(42_658)),
        ]);
    }

    #[Test]
    public function a_picked_up_drop_joins_the_kept_items_with_how_many_are_held_now(): void
    {
        $drops = new Drops([42_657 => self::token(1)]);
        $picked = new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(4));

        $empty = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([]), new Quantity(30)),
            $picked,
            self::me(),
            $drops,
        );
        $holding = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30)),
            $picked,
            self::me(),
        );

        static::assertSame(4, $empty->permanent->find(new ItemId(42_657))->unwrap()->quantity->value);
        static::assertSame(4, $holding->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_picked_up_drop_it_never_saw_is_left_for_the_next_load(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([]), new Quantity(30));

        static::assertSame($inventory, InventoryTracking::follow(
            $inventory,
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
            self::me(),
        ));
    }

    #[Test]
    public function other_events_leave_the_inventory_as_it_is(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30));

        static::assertSame($inventory, InventoryTracking::follow($inventory, new ResurrectedEvent(), self::me()));
    }

    #[Test]
    public function an_enhanced_item_stays_equipped_and_takes_the_new_enhancement(): void
    {
        $staff = new PlayerItem(
            new Weapon(
                new ItemId(3),
                new ItemName('Default Staff'),
                new ItemLevel(1),
                WeaponType::Staff,
                new WeaponDamage(new DamageVariance(10)),
                Option\none(),
            ),
            new Quantity(1),
            equipped: true,
        );
        $lucky = new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none());

        $enhanced = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([$staff, self::token(2)]), new Quantity(30)),
            new ItemsEnhancedEvent([new ItemId(3)], $lucky, new Gold(1010)),
            self::me(),
        );

        $after = $enhanced->permanent->find(new ItemId(3))->unwrap();
        static::assertTrue($after->equipped);
        static::assertInstanceOf(Weapon::class, $after->item);
        static::assertSame($lucky, $after->item->enhancement->unwrap());
        static::assertSame(2, $enhanced->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function the_bag_only_changes_size_when_my_data_says_a_different_size(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30));

        $theirs = InventoryTracking::follow($inventory, new PlayerDataLoadedEvent([self::data(1, 131)]), self::me());
        $same = InventoryTracking::follow($inventory, new PlayerDataLoadedEvent([self::data(122_628, 30)]), self::me());
        $bigger = InventoryTracking::follow(
            $inventory,
            new PlayerDataLoadedEvent([self::data(1, 131), self::data(122_628, 131)]),
            self::me(),
        );

        static::assertSame($inventory, $theirs);
        static::assertSame($inventory, $same);
        static::assertSame(131, $bigger->slots->value);
    }

    #[Test]
    public function enhancing_an_item_it_does_not_hold_changes_nothing(): void
    {
        $lucky = new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none());

        $enhanced = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([self::token(2)]), new Quantity(30)),
            new ItemsEnhancedEvent([new ItemId(3)], $lucky, new Gold(1010)),
            self::me(),
        );

        static::assertTrue($enhanced->find(new ItemId(3))->isNone());
        static::assertSame(2, $enhanced->quantity(new ItemId(42_657)));
    }

    private static function data(int $socketId, int $bagSlots): PlayerData
    {
        return new PlayerData(
            new SocketId($socketId),
            new PlayerName('Hero'),
            new PlayerLevelProgress(new PlayerLevel(1), new Gauge(new Experience(0), new Experience(1000))),
            new Wallet(new Gold(0), new AdventureCoins(0)),
            false,
            new Quantity($bagSlots),
            new PlayerQuestMarks(),
        );
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('Hero'));
    }

    private static function ore(int $quantity): PlayerItem
    {
        return new PlayerItem(
            new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            new Quantity($quantity),
        );
    }

    private static function token(int $quantity): PlayerItem
    {
        return new PlayerItem(
            new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1)),
            new Quantity($quantity),
        );
    }

    private static function turnedIn(int $item, int $quantity): ItemsTurnedInEvent
    {
        return new ItemsTurnedInEvent([new TurnedInItem(new ItemId($item), new Quantity($quantity))]);
    }
}
