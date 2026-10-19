<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\AwayTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\AwayFromKeyboardEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AwayTrackingTest extends TestCase
{
    #[Test]
    public function it_is_away_once_the_server_flags_it(): void
    {
        $flagged = AwayFromKeyboardEvent::from(
            DelimitedMessage::from('%xt%server%1%You have been flagged as away from keyboard.%')->unwrap(),
        )->unwrap();

        static::assertTrue(AwayTracking::follow(false, $flagged, self::me()));
        static::assertFalse(AwayTracking::follow(false, new PlayerDiedEvent(), self::me()));
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('lorekeeper03'));
    }
}
