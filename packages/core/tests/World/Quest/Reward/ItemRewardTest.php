<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Reward;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Quest\Reward\DropRate;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ItemRewardType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class ItemRewardTest extends TestCase
{
    #[Test]
    public function it_holds_item_drop_rate_quantity_and_type(): void
    {
        $reward = new ItemReward(new ItemId(1234), new DropRate(10.0), 3, ItemRewardType::Random);

        static::assertSame(1234, $reward->itemId->value);
        static::assertSame(10.0, $reward->dropRate->percentage);
        static::assertSame(3, $reward->quantity);
        static::assertSame(ItemRewardType::Random, $reward->type);
    }

    #[Test]
    public function it_rejects_a_zero_quantity(): void
    {
        $this->expectException(AssertException::class);

        new ItemReward(new ItemId(1234), new DropRate(100.0), 0, ItemRewardType::Guaranteed);
    }
}
