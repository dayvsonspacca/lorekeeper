<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\HeroInventory;
use Lorekeeper\Core\Player\Hero\HeroItem;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class HeroInventoryTest extends TestCase
{
    #[Test]
    public function it_finds_an_item_whether_it_is_kept_or_temporary(): void
    {
        $healer = new HeroItem(
            new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none()),
        );
        $ore = new HeroItem(new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)));
        $inventory = new HeroInventory(new Inventory([$healer]), new Quantity(131), new Inventory([$ore]));

        static::assertSame($healer, $inventory->find(new ItemId(15_651))->unwrap());
        static::assertSame($ore, $inventory->find(new ItemId(42_658))->unwrap());
        static::assertTrue($inventory->find(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_tells_how_many_of_an_item_it_holds(): void
    {
        $inventory = new HeroInventory(
            new Inventory([new HeroItem(
                new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1)),
                new Quantity(3),
            )]),
            new Quantity(131),
            new Inventory([new HeroItem(
                new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            )]),
        );

        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
        static::assertSame(1, $inventory->quantity(new ItemId(42_658)));
        static::assertSame(0, $inventory->quantity(new ItemId(1)));
    }

    #[Test]
    public function it_starts_with_nothing_temporary(): void
    {
        static::assertCount(0, new HeroInventory(new Inventory([]), new Quantity(131))->temporary);
    }

    #[Test]
    public function it_tells_how_many_slots_its_bag_has(): void
    {
        static::assertSame(131, new HeroInventory(new Inventory([]), new Quantity(131))->slots->value);
    }

    #[Test]
    public function it_rejects_an_item_both_kept_and_temporary(): void
    {
        $ore = new HeroItem(new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)));

        $this->expectException(InvalidArgumentException::class);

        new HeroInventory(new Inventory([$ore]), new Quantity(131), new Inventory([$ore]));
    }
}
