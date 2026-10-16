<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Script\Game\OpenShopScript;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OpenShopScriptTest extends TestCase
{
    #[Test]
    public function it_opens_the_shop_and_returns_it(): void
    {
        $shop = new ShopLoadedEvent(new ShopId(31), new ShopName('Battleon Assorted shop'), false, [], []);

        [$instructions, $result] = Replay::run(new OpenShopScript(new AreaId(1350), new ShopId(31)), $shop);

        static::assertSame($shop, $result);
        static::assertInstanceOf(Send::class, $instructions[0]);
        static::assertSame('%xt%zm%loadShop%1350%31%', $instructions[0]->command->toMessage());
        static::assertSame([ShopLoadedEvent::class], $instructions[0]->await?->events);
    }
}
