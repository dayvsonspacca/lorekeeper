<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Quest\TurnInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class TurnInItemTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $turnIn = new TurnInItem(new ItemId(1234), 10);

        static::assertSame(1234, $turnIn->itemId->value);
        static::assertSame(10, $turnIn->quantity);
    }

    #[Test]
    public function it_rejects_a_zero_quantity(): void
    {
        $this->expectException(AssertException::class);

        new TurnInItem(new ItemId(1234), 0);
    }
}
