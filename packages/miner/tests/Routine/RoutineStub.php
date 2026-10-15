<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Miner\Connection;
use Lorekeeper\Miner\Login\GameLogin;
use Lorekeeper\Miner\Routine\GameEntry;
use Lorekeeper\Miner\Session;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\ClientVersion;
use Lorekeeper\Protocol\Event\LoginSucceededEvent;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\RoomId;
use Lorekeeper\Protocol\SocketId;
use PHPUnit\Framework\MockObject\Stub;
use Psl\DateTime\Duration;
use Psl\TCP;

final readonly class RoutineStub
{
    public static function readySession(): Session
    {
        $session = new Session();
        $session->observe(new LoginSucceededEvent(new SocketId(59_878)));
        $session->observe(new MovedToMapEvent(new AreaId(172_760), new RoomId(2), new MapName('battleon'), []));

        return $session;
    }

    /** @param list<string> $sent */
    public static function entry(
        TCP\StreamInterface&Stub $stream,
        array &$sent,
        Session $session,
        ?Duration $timeout = null,
    ): GameEntry {
        $stream
            ->method('writeAll')
            ->willReturnCallback(static function (string $data) use (&$sent): void {
                $sent[] = rtrim($data, characters: "\0");
            });

        return new GameEntry(
            new Connection($stream),
            new GameLogin(new PlayerName('Hero'), 'token', new ClientVersion('5.1')),
            $session,
            static fn(string $line): null => null,
            $timeout,
        );
    }
}
