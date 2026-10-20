<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\AwayTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\PlayerAwayUpdatedEvent;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AwayTrackingTest extends TestCase
{
    #[Test]
    public function it_goes_away_and_comes_back_when_the_server_says_so_about_it(): void
    {
        $away = new PlayerAwayUpdatedEvent(new PlayerName('LoreKeeper03'), away: true);
        $back = new PlayerAwayUpdatedEvent(new PlayerName('lorekeeper03'), away: false);
        $someoneElse = new PlayerAwayUpdatedEvent(new PlayerName('Someone'), away: true);

        static::assertTrue(AwayTracking::follow(false, $away, self::me()));
        static::assertFalse(AwayTracking::follow(true, $back, self::me()));
        static::assertFalse(AwayTracking::follow(false, $someoneElse, self::me()));
        static::assertFalse(AwayTracking::follow(false, new ResurrectedEvent(), self::me()));
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('lorekeeper03'));
    }
}
