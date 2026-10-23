<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassItem;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class InventoryTest extends TestCase
{
    #[Test]
    public function it_finds_an_item_by_its_id(): void
    {
        $scales = new Resource(new ItemId(11_475), new ItemName('Dragon Scale'), new ItemLevel(1), new Quantity(1000));
        $inventory = new Inventory([
            new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none()),
            $scales,
        ]);

        static::assertCount(2, $inventory);
        static::assertSame($scales, $inventory->find(new ItemId(11_475))->unwrap());
        static::assertTrue($inventory->find(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_rejects_the_same_item_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Inventory([
            new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none()),
            new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none()),
        ]);
    }
}
