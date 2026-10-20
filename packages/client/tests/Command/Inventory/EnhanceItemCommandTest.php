<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Inventory;

use Lorekeeper\Client\Command\Inventory\EnhanceItemCommand;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnhanceItemCommandTest extends TestCase
{
    #[Test]
    public function it_buys_an_enhancement_from_a_shop_and_applies_it_to_the_item(): void
    {
        $command = new EnhanceItemCommand(new AreaId(190_023), new ItemId(3), new ItemId(3628), new ShopId(147));

        static::assertSame('%xt%zm%enhanceItemShop%190023%3%3628%147%', $command->toMessage());
    }
}
