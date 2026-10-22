<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Map;

use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\RoomId;
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

    #[Test]
    public function it_moves_into_a_room_unless_it_already_names_one(): void
    {
        $public = new JoinMapCommand(new PlayerName('Hero2'), new MapName('boxes'), Option\none());
        $chosen = new JoinMapCommand(new PlayerName('Hero2'), new MapName('boxes'), Option\some(new RoomId(7)));

        static::assertSame('%xt%zm%cmd%1%tfer%Hero2%boxes-24357%', $public->inRoom(new RoomId(24_357))->toMessage());
        static::assertSame('%xt%zm%cmd%1%tfer%Hero2%boxes-7%', $chosen->inRoom(new RoomId(24_357))->toMessage());
    }
}
