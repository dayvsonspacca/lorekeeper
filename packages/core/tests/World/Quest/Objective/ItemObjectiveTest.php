<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Objective;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Quest\Objective\ItemObjective;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class ItemObjectiveTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $objective = new ItemObjective(new ItemId(1234), 10);

        static::assertSame(1234, $objective->itemId->value);
        static::assertSame(10, $objective->quantity);
    }

    #[Test]
    public function it_rejects_a_zero_quantity(): void
    {
        $this->expectException(AssertException::class);

        new ItemObjective(new ItemId(1234), 0);
    }
}
