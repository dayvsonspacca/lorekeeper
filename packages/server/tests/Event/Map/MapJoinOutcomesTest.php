<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapJoinedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRefusedEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapJoinOutcomesTest extends TestCase
{
    #[Test]
    public function being_in_the_map_is_joining_it_and_every_refusal_is_a_refusal(): void
    {
        static::assertTrue(is_a(MovedToMapEvent::class, MapJoinedEvent::class, allow_string: true));
        static::assertTrue(is_a(AlreadyInMapEvent::class, MapJoinedEvent::class, allow_string: true));

        foreach ([
            MapLockedEvent::class,
            MapMemberOnlyEvent::class,
            MapNotAvailableEvent::class,
            MapRequiresQuestEvent::class,
        ] as $refusal) {
            static::assertTrue(is_a($refusal, MapRefusedEvent::class, allow_string: true));
            static::assertFalse(is_a($refusal, MapJoinedEvent::class, allow_string: true));
        }
    }
}
