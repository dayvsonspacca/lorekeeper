<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Objective;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Objective\ItemObjective;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemObjectiveTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $objective = new ItemObjective(new ItemId(1234), new Quantity(10));

        static::assertSame(1234, $objective->itemId->value);
        static::assertSame(10, $objective->quantity->value);
    }
}
