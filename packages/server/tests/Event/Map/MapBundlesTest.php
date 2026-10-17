<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapBundlesTest extends TestCase
{
    #[Test]
    public function being_in_the_map_is_a_join_and_every_refusal_is_a_refusal(): void
    {
        static::assertSame([MovedToMapEvent::class, AlreadyInMapEvent::class], MapJoins::events());
        static::assertSame(
            [
                MapLockedEvent::class,
                MapMemberOnlyEvent::class,
                MapNotAvailableEvent::class,
                MapRequiresQuestEvent::class,
            ],
            MapRefusals::events(),
        );
    }

    #[Test]
    public function a_bundle_tells_whether_an_answer_belongs_to_it(): void
    {
        $locked = new MapLockedEvent();
        $memberOnly = new MapMemberOnlyEvent(new MapName('yulgar'));

        static::assertTrue(MapRefusals::contains($locked));
        static::assertTrue(MapRefusals::contains($memberOnly));
        static::assertFalse(MapJoins::contains($locked));
        static::assertTrue(MapJoins::contains(new AlreadyInMapEvent()));
        static::assertFalse(MapRefusals::contains(null));
    }
}
