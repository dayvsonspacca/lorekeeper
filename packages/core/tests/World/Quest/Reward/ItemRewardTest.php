<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Reward;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Reward\DropRate;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ItemRewardType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemRewardTest extends TestCase
{
    #[Test]
    public function it_holds_item_drop_rate_quantity_and_type(): void
    {
        $gem = new Resource(new ItemId(1234), new ItemName('Realm Gem'), new ItemLevel(1));
        $reward = new ItemReward($gem, new DropRate(10.0), new Quantity(3), ItemRewardType::Random);

        static::assertSame($gem, $reward->item);
        static::assertSame(10.0, $reward->dropRate->percentage);
        static::assertSame(3, $reward->quantity->value);
        static::assertSame(ItemRewardType::Random, $reward->type);
    }
}
