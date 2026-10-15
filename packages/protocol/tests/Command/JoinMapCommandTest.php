<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Command;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Protocol\Command\JoinMapCommand;
use Lorekeeper\Protocol\RoomId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class JoinMapCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_without_room(): void
    {
        $command = new JoinMapCommand(new PlayerName('Hero'), new MapName('battleon'), Option\none());

        static::assertSame('%xt%zm%cmd%1%tfer%Hero%battleon%', $command->toMessage());
    }

    #[Test]
    public function it_serializes_with_room(): void
    {
        $command = new JoinMapCommand(new PlayerName('Hero'), new MapName('battleon'), Option\some(new RoomId(2)));

        static::assertSame('%xt%zm%cmd%1%tfer%Hero%battleon-2%', $command->toMessage());
    }
}
