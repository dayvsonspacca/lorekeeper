<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command;

use Lorekeeper\Client\Command\LoadShopCommand;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
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
