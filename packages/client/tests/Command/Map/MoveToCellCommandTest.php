<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Map;

use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoveToCellCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_the_walk_into_a_cell(): void
    {
        static::assertSame(
            '%xt%zm%moveToCell%732692%r4%Bottom%',
            new MoveToCellCommand(new AreaId(732_692), new MapSpot(new MapCell('r4'), MapPad::Bottom))->toMessage(),
        );
        static::assertSame(
            '%xt%zm%moveToCell%732692%Enter%Center%',
            new MoveToCellCommand(new AreaId(732_692), new MapSpot(new MapCell('Enter'), MapPad::Center))->toMessage(),
        );
    }
}
