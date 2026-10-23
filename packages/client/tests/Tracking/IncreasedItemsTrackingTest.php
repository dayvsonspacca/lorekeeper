<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsIncreasedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IncreasedItemsTrackingTest extends TestCase
{
    #[Test]
    public function more_of_a_quest_item_grows_the_temporary_part_and_more_of_a_kept_item_the_kept_one(): void
    {
        $inventory = new HeroInventory(new Quantity(30), new Quantity(30));
        $inventory->add(
            new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1), new Quantity(99)),
            new Quantity(3),
        );
        $slain = new Resource(new ItemId(5574), new ItemName('Slain Gorillaphant'), new ItemLevel(1), new Quantity(99));
        $slain->tags->add(ItemTag::Temporary);
        $inventory->add($slain);

        $me = new Identity(new PlayerSpawnId(122_628), new PlayerName('Hero'));
        $grew = InventoryTracking::follow(
            $inventory,
            new QuestItemsIncreasedEvent([
                new ItemIncrease(new ItemId(5574), new Quantity(49)),
                new ItemIncrease(new ItemId(42_657), new Quantity(5)),
            ]),
            $me,
        );
        $kept = InventoryTracking::follow(
            $inventory,
            new ItemsIncreasedEvent([
                new ItemIncrease(new ItemId(42_657), new Quantity(1), new Quantity(11)),
                new ItemIncrease(new ItemId(5574), new Quantity(1), new Quantity(99)),
                new ItemIncrease(new ItemId(1), new Quantity(1), new Quantity(1)),
            ]),
            $me,
        );
        $nothing = InventoryTracking::follow(
            $inventory,
            new ItemsIncreasedEvent([new ItemIncrease(new ItemId(1), new Quantity(1))]),
            $me,
        );

        static::assertTrue($grew);
        static::assertTrue($kept);
        static::assertFalse($nothing);
        static::assertSame(50, $inventory->temporary->quantity(new ItemId(5574)));
        static::assertSame(11, $inventory->permanent->quantity(new ItemId(42_657)));
        static::assertTrue($inventory->find(new ItemId(1))->isNone());
    }
}
