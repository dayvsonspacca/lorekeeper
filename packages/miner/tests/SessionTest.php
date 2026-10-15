<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Miner\Session;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Event\AwayFromKeyboardEvent;
use Lorekeeper\Protocol\Event\LoginSucceededEvent;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Protocol\RoomId;
use Lorekeeper\Protocol\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    #[Test]
    public function it_starts_empty(): void
    {
        $session = new Session();

        static::assertTrue($session->socketId()->isNone());
        static::assertTrue($session->areaId()->isNone());
        static::assertTrue($session->mapName()->isNone());
    }

    #[Test]
    public function it_tracks_the_socket_after_login(): void
    {
        $session = new Session();

        $session->observe(new LoginSucceededEvent(new SocketId(59_878)));

        static::assertSame(59_878, $session->socketId()->unwrap()->value);
    }

    #[Test]
    public function it_tracks_the_current_map(): void
    {
        $session = new Session();

        $session->observe(new MovedToMapEvent(new AreaId(174_547), new RoomId(1), new MapName('forest'), []));

        static::assertSame(174_547, $session->areaId()->unwrap()->value);
        static::assertSame('forest', $session->mapName()->unwrap()->value);
    }

    #[Test]
    public function it_remembers_which_shops_answered(): void
    {
        $session = new Session();
        $session->observe(new ShopLoadedEvent(new ShopId(31), []));

        static::assertTrue($session->hasLoaded(new ShopId(31)));
        static::assertFalse($session->hasLoaded(new ShopId(299)));
    }

    #[Test]
    public function it_ignores_other_events(): void
    {
        $session = new Session();

        $session->observe(new AwayFromKeyboardEvent());

        static::assertTrue($session->socketId()->isNone());
    }
}
