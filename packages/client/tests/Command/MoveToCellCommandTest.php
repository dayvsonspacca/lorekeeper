<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command;

use Lorekeeper\Client\Command\MoveToCellCommand;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\Pad;
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
            new MoveToCellCommand(new AreaId(732_692), new Cell('r4'), Pad::Bottom)->toMessage(),
        );
        static::assertSame(
            '%xt%zm%moveToCell%732692%Enter%Center%',
            new MoveToCellCommand(new AreaId(732_692), new Cell('Enter'), Pad::Center)->toMessage(),
        );
    }
}
