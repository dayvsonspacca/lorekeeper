<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script;

use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class SendTest extends TestCase
{
    #[Test]
    public function it_awaits_events_and_opens_bundles_for_as_long_as_it_is_told(): void
    {
        $send = new Send(new LogoutCommand())->awaits(
            Duration::seconds(5),
            MovedToMapEvent::class,
            MapRefusals::class,
            MapLockedEvent::class,
            AlreadyInMapEvent::class,
        );

        static::assertEquals(
            new Await([
                MovedToMapEvent::class,
                MapLockedEvent::class,
                MapMemberOnlyEvent::class,
                MapNotAvailableEvent::class,
                MapRequiresQuestEvent::class,
                AlreadyInMapEvent::class,
            ], Duration::seconds(5)),
            $send->await,
        );
    }

    #[Test]
    public function a_send_waits_for_nothing_until_told(): void
    {
        static::assertNull(new Send(new LogoutCommand())->await);
    }
}
