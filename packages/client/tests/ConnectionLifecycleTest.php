<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\PeerClosedException;
use Lorekeeper\Server\Server;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\IO;
use Psl\Network\Address;
use Psl\TCP;

final class ConnectionLifecycleTest extends TestCase
{
    #[Test]
    public function it_opens_a_tcp_connection_and_closes_it_here(): void
    {
        $listener = stream_socket_server('tcp://127.0.0.1:0');
        static::assertNotFalse($listener);
        $port = (int) substr(
            (string) strrchr((string) stream_socket_get_name($listener, remote: false), needle: ':'),
            offset: 1,
        );

        $server = new Server('Twilly', Address::tcp('127.0.0.1', $port), true, false);
        $connection = Connection::open($server);
        static::assertFalse($connection->closedHere());
        static::assertSame($server, $connection->server());
        static::assertNull(new Connection(static::createStub(TCP\StreamInterface::class))->server());

        $connection->close();
        fclose($listener);

        static::assertTrue($connection->closedHere());
    }

    #[Test]
    public function it_reports_errors_while_receiving_and_refuses_to_send_after(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willThrowException(new IO\Exception\RuntimeException('Connection reset'));
        $connection = new Connection($stream);

        try {
            $connection->receive();
            static::fail('A broken connection should not receive.');
        } catch (PeerClosedException $closed) {
            static::assertSame('Connection error while receiving: Connection reset', $closed->getMessage());
        }

        $this->expectExceptionMessage('Connection error while receiving: Connection reset');

        $connection->send(new LogoutCommand());
    }
}
