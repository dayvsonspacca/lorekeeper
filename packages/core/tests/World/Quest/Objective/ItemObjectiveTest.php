<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Objective;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Objective\ItemObjective;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemObjectiveTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $gem = new Resource(new ItemId(1234), new ItemName('Realm Gem'));
        $objective = new ItemObjective($gem, new Quantity(10));

        static::assertSame($gem, $objective->item);
        static::assertSame(10, $objective->quantity->value);
    }
}
