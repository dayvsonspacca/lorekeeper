<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Drops;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\HeldItem;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Player\Hero\HeroData;
use Lorekeeper\Server\Event\Player\Hero\HeroDataLoadedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

/** @mago-expect lint:too-many-methods */
final class InventoryTrackingTest extends TestCase
{
    #[Test]
    public function quest_items_pile_up_as_temporary_and_leave_when_turned_in(): void
    {
        $inventory = self::holding(self::token(3));

        static::assertTrue(InventoryTracking::follow(
            $inventory,
            new QuestItemsReceivedEvent([self::ore(1)]),
            self::me(),
        ));
        InventoryTracking::follow($inventory, new QuestItemsReceivedEvent([self::ore(2)]), self::me());
        static::assertSame(3, $inventory->temporary->quantity(new ItemId(42_658)));

        static::assertTrue(InventoryTracking::follow($inventory, self::turnedIn(42_658, 2), self::me()));
        static::assertSame(1, $inventory->quantity(new ItemId(42_658)));

        InventoryTracking::follow($inventory, self::turnedIn(42_658, 1), self::me());
        static::assertTrue($inventory->find(new ItemId(42_658))->isNone());
        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_quest_can_take_kept_items_too(): void
    {
        $inventory = self::holding(self::token(3));

        InventoryTracking::follow($inventory, self::turnedIn(42_657, 3), self::me());

        static::assertTrue($inventory->find(new ItemId(42_657))->isNone());
    }

    #[Test]
    public function turning_in_an_item_it_does_not_hold_changes_nothing(): void
    {
        $inventory = self::holding(self::token(3));

        static::assertFalse(InventoryTracking::follow($inventory, self::turnedIn(42_658, 1), self::me()));
        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function loading_the_inventory_replaces_the_kept_items_and_keeps_the_temporary_ones(): void
    {
        $inventory = self::holding(self::ore(1), new HeldItem(self::staff()));

        static::assertTrue(InventoryTracking::follow(
            $inventory,
            new InventoryLoadedEvent([self::token(5)]),
            self::me(),
        ));

        static::assertSame([5, 1], [
            $inventory->quantity(new ItemId(42_657)),
            $inventory->quantity(new ItemId(42_658)),
        ]);
        static::assertTrue($inventory->find(new ItemId(3))->isNone());
    }

    #[Test]
    public function loading_the_inventory_puts_on_what_is_equipped_and_worn(): void
    {
        $inventory = self::holding();

        InventoryTracking::follow(
            $inventory,
            new InventoryLoadedEvent([new HeldItem(self::staff(), equipped: true, worn: true), self::token(2)]),
            self::me(),
        );

        static::assertTrue($inventory->equipment->equips(new ItemId(3)));
        static::assertTrue($inventory->costume->wears(new ItemId(3)));
        static::assertSame(2, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_picked_up_drop_joins_the_kept_items_with_how_many_are_held_now(): void
    {
        $drops = new Drops([42_657 => self::token(1)]);
        $picked = new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(4));
        $empty = self::holding();
        $holding = self::holding(self::token(3));

        static::assertTrue(InventoryTracking::follow($empty, $picked, self::me(), $drops));
        static::assertTrue(InventoryTracking::follow($holding, $picked, self::me()));

        static::assertSame(4, $empty->permanent->quantity(new ItemId(42_657)));
        static::assertSame(4, $holding->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_picked_up_drop_it_never_saw_is_left_for_the_next_load(): void
    {
        $inventory = self::holding();

        static::assertFalse(InventoryTracking::follow(
            $inventory,
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
            self::me(),
        ));
        static::assertTrue($inventory->find(new ItemId(42_657))->isNone());
    }

    #[Test]
    public function other_events_leave_the_inventory_as_it_is(): void
    {
        static::assertFalse(InventoryTracking::follow(
            self::holding(self::token(3)),
            new ResurrectedEvent(),
            self::me(),
        ));
    }

    #[Test]
    public function an_enhanced_item_stays_equipped_and_takes_the_new_enhancement(): void
    {
        $staff = self::staff();
        $inventory = self::holding(new HeldItem($staff), self::token(2));
        $inventory->equipment->equip($staff);
        $lucky = new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none());

        static::assertTrue(InventoryTracking::follow(
            $inventory,
            new ItemsEnhancedEvent([new ItemId(3)], $lucky, new Gold(1010)),
            self::me(),
        ));

        static::assertTrue($inventory->equipment->equips(new ItemId(3)));
        static::assertSame($lucky, $staff->enhancement->unwrap());
        static::assertSame(2, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function enhancing_an_item_it_does_not_hold_changes_nothing(): void
    {
        $inventory = self::holding(self::token(2));
        $lucky = new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none());

        static::assertFalse(InventoryTracking::follow(
            $inventory,
            new ItemsEnhancedEvent([new ItemId(3), new ItemId(42_657)], $lucky, new Gold(1010)),
            self::me(),
        ));
        static::assertTrue($inventory->find(new ItemId(3))->isNone());
    }

    #[Test]
    public function the_bag_and_bank_only_change_size_when_my_data_says_a_different_size(): void
    {
        $inventory = self::holding(self::token(3));

        static::assertFalse(InventoryTracking::follow(
            $inventory,
            new HeroDataLoadedEvent(self::data(1, 131, 60)),
            self::me(),
        ));
        static::assertFalse(InventoryTracking::follow(
            $inventory,
            new HeroDataLoadedEvent(self::data(122_628, 30, 30)),
            self::me(),
        ));
        static::assertTrue(InventoryTracking::follow(
            $inventory,
            new HeroDataLoadedEvent(self::data(122_628, 131, 30)),
            self::me(),
        ));
        static::assertTrue(InventoryTracking::follow(
            $inventory,
            new HeroDataLoadedEvent(self::data(122_628, 131, 60)),
            self::me(),
        ));

        static::assertSame([131, 60], [$inventory->slots->value, $inventory->bank->slots->value]);
    }

    private static function holding(HeldItem ...$items): HeroInventory
    {
        $inventory = new HeroInventory(new Quantity(30), new Quantity(30));
        foreach ($items as $held) {
            $inventory->add($held->item, $held->quantity);
        }

        return $inventory;
    }

    private static function data(int $spawnId, int $bagSlots, int $bankSlots): HeroData
    {
        return new HeroData(
            new PlayerSpawnId($spawnId),
            new PlayerName('Hero'),
            new PlayerLevel(1),
            new Gauge(new Experience(0), new Experience(1000)),
            new HeroWallet(new Gold(0), new AdventureCoins(0)),
            false,
            new Quantity($bagSlots),
            new Quantity($bankSlots),
            [],
        );
    }

    private static function me(): Identity
    {
        return new Identity(new PlayerSpawnId(122_628), new PlayerName('Hero'));
    }

    private static function ore(int $quantity): HeldItem
    {
        $ore = new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1), new Quantity(10));
        $ore->tags->add(ItemTag::Temporary);

        return new HeldItem($ore, new Quantity($quantity));
    }

    private static function token(int $quantity): HeldItem
    {
        return new HeldItem(
            new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1), new Quantity(99)),
            new Quantity($quantity),
        );
    }

    private static function staff(): Weapon
    {
        return new Weapon(
            new ItemId(3),
            new ItemName('Default Staff'),
            new ItemLevel(1),
            WeaponType::Staff,
            new WeaponDamage(new DamageVariance(10)),
            Option\none(),
        );
    }

    private static function turnedIn(int $item, int $quantity): ItemsTurnedInEvent
    {
        return new ItemsTurnedInEvent([new TurnedInItem(new ItemId($item), new Quantity($quantity))]);
    }
}
