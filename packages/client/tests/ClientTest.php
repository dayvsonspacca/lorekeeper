<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Generator;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Command\LogoutCommand;
use Lorekeeper\Client\Command\PolicyFileRequestCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\LoginSucceededEvent;
use Lorekeeper\Server\Message\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class ClientTest extends TestCase
{
    #[Test]
    public function it_sends_and_hands_the_awaited_event_back_to_the_script(): void
    {
        $sent = [];
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream
            ->method('writeAll')
            ->willReturnCallback(static function (string $bytes) use (&$sent): void {
                $sent[] = $bytes;
            });
        $stream->method('read')->willReturnOnConsecutiveCalls("%xt%loginResponse%-1%true%59878%Hero%Welcome!%\0");
        $stream->method('reachedEndOfDataSource')->willReturn(false);

        $result = new Client(new Connection($stream))->run(self::script(
            static fn(): Generator => yield new Send(
                new PolicyFileRequestCommand(),
                new Await([LoginSucceededEvent::class], Duration::seconds(5)),
            ),
        ));

        static::assertInstanceOf(LoginSucceededEvent::class, $result);
        static::assertSame(59_878, $result->socketId->value);
        static::assertSame(["<policy-file-request/>\0"], $sent);
    }

    #[Test]
    public function it_skips_what_it_was_not_waiting_for_and_tells_the_observer(): void
    {
        $unheard = [];
        $client = new Client(
            self::connection("%xt%server%-1%Profanity filter On.%\0", "unparsable\0", "<cross-domain-policy/>\0"),
            static function (Message $message) use (&$unheard): void {
                $unheard[] = $message->raw;
            },
        );

        $result = $client->run(self::script(
            static fn(): Generator => yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5)),
        ));

        static::assertInstanceOf(ConnectionEstablishedEvent::class, $result);
        static::assertSame(['%xt%server%-1%Profanity filter On.%'], $unheard);
    }

    #[Test]
    public function it_tells_the_script_when_the_wait_is_over(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willThrowException(new CancelledException(new NullCancellationToken()));
        $stream->method('reachedEndOfDataSource')->willReturn(false);
        $client = new Client(new Connection($stream));

        $waited = $client->run(self::script(
            static fn(): Generator => yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5)),
        ));
        $expired = $client->run(self::script(
            static fn(): Generator => yield new Await([ConnectionEstablishedEvent::class], Duration::zero()),
        ));

        static::assertInstanceOf(TimedOut::class, $waited);
        static::assertSame([ConnectionEstablishedEvent::class], $waited->await->events);
        static::assertInstanceOf(TimedOut::class, $expired);
    }

    #[Test]
    public function it_pauses_and_sends_without_waiting(): void
    {
        $answers = new Client(self::connection())->run(self::script(static function (): Generator {
            $paused = yield new Pause(Duration::milliseconds(1));
            $sent = yield new Send(new LogoutCommand());
            $unknown = yield new class() implements Instruction {};

            return [$paused, $sent, $unknown];
        }));

        static::assertSame([null, null, null], $answers);
    }

    #[Test]
    public function it_returns_a_disconnection_instead_of_failing(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturn('');
        $stream->method('reachedEndOfDataSource')->willReturn(true);

        $result = new Client(new Connection($stream))->run(self::script(static function (): Generator {
            yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5));

            return 'never';
        }));

        static::assertEquals(new Disconnected('The server closed the connection.'), $result);
    }

    /**
     * @param callable(): Generator $steps
     *
     * @return Script<mixed>
     */
    private static function script(callable $steps): Script
    {
        return new readonly class($steps) implements Script {
            /** @param callable(): Generator $steps */
            public function __construct(
                private mixed $steps,
            ) {}

            public function run(): Generator
            {
                return ($this->steps)();
            }
        };
    }

    private static function connection(string ...$reads): Connection
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturnOnConsecutiveCalls(...$reads);
        $stream->method('reachedEndOfDataSource')->willReturn(false);

        return new Connection($stream);
    }
}
