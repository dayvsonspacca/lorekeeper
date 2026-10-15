<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Command;

use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Command\LoadShopCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoadShopCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_the_shop_request(): void
    {
        static::assertSame(
            '%xt%zm%loadShop%172760%31%',
            new LoadShopCommand(new AreaId(172_760), new ShopId(31))->toMessage(),
        );
    }
}
