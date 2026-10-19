<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\PeerClosedException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\IO;
use Psl\TCP;

final class ConnectionTest extends TestCase
{
    #[Test]
    public function it_sends_commands_terminated_by_a_null_byte(): void
    {
        $stream = $this->createMock(TCP\StreamInterface::class);
        $stream->expects(static::once())->method('writeAll')->with("%xt%zm%logout%1%\0");

        new Connection($stream)->send(new LogoutCommand());
    }

    #[Test]
    public function it_throws_when_sending_on_a_closed_connection(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('writeAll')->willThrowException(new IO\Exception\RuntimeException('Broken pipe'));

        $this->expectException(PeerClosedException::class);

        new Connection($stream)->send(new LogoutCommand());
    }

    #[Test]
    public function it_receives_a_message(): void
    {
        $connection = self::connection("%xt%server%-1%Profanity filter On.%\0");

        static::assertInstanceOf(DelimitedMessage::class, $connection->receive()->unwrap());
    }

    #[Test]
    public function it_receives_a_message_split_across_reads(): void
    {
        $connection = self::connection('{"b":{"o":', "{\"cmd\":\"moveToArea\"}}}\0");

        $message = $connection->receive()->unwrap();

        static::assertInstanceOf(JsonMessage::class, $message);
        static::assertSame('moveToArea', $message->data['cmd']);
    }

    #[Test]
    public function it_receives_consecutive_messages_from_one_read(): void
    {
        $connection = self::connection("%xt%server%-1%first%\0%xt%server%-1%second%\0");

        static::assertSame('%xt%server%-1%first%', $connection->receive()->unwrap()->raw);
        static::assertSame('%xt%server%-1%second%', $connection->receive()->unwrap()->raw);
    }

    #[Test]
    public function it_decompresses_compressed_messages(): void
    {
        $connection = self::connection(
            'Z' . base64_encode((string) gzcompress('{"b":{"o":{"cmd":"moveToArea"}}}')) . "\0",
        );

        static::assertInstanceOf(JsonMessage::class, $connection->receive()->unwrap());
    }

    #[Test]
    public function it_returns_none_for_unknown_messages(): void
    {
        $connection = self::connection("hello\0");

        static::assertTrue($connection->receive()->isNone());
    }

    #[Test]
    public function it_throws_when_the_peer_closes(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturn('');
        $stream->method('reachedEndOfDataSource')->willReturn(true);

        $this->expectException(PeerClosedException::class);

        new Connection($stream)->receive();
    }

    #[Test]
    public function it_refuses_to_send_after_the_server_closed(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturn('');
        $stream->method('reachedEndOfDataSource')->willReturn(true);
        $connection = new Connection($stream);

        try {
            $connection->receive();
            static::fail('The closed connection should not receive.');
        } catch (PeerClosedException $closed) {
            static::assertSame('The server closed the connection.', $closed->getMessage());
        }

        $this->expectExceptionMessage('The server closed the connection.');

        $connection->send(new LogoutCommand());
    }

    private static function connection(string ...$reads): Connection
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturnOnConsecutiveCalls(...$reads);
        $stream->method('reachedEndOfDataSource')->willReturn(false);

        return new Connection($stream);
    }
}
