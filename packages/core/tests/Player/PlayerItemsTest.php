<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerItemsTest extends TestCase
{
    #[Test]
    public function it_finds_an_item_by_its_id(): void
    {
        $scales = new PlayerItem(new Resource(new ItemId(11_475), new ItemName('Dragon Scale')), new Quantity(5));
        $inventory = new PlayerItems([
            new PlayerItem(new ClassItem(new ItemId(15_651), new ItemName('Healer'))),
            $scales,
        ]);

        static::assertCount(2, $inventory->items);
        static::assertSame($scales, $inventory->find(new ItemId(11_475))->unwrap());
        static::assertTrue($inventory->find(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_rejects_the_same_item_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlayerItems([
            new PlayerItem(new ClassItem(new ItemId(15_651), new ItemName('Healer'))),
            new PlayerItem(new ClassItem(new ItemId(15_651), new ItemName('Healer'))),
        ]);
    }
}
