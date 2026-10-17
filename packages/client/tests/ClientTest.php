<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Generator;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Command\Session\PolicyFileRequestCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Lorekeeper\Server\Message\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\Async\SignalCancellationToken;
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
    public function it_skips_what_it_was_not_waiting_for_and_tells_the_observer_about_every_message(): void
    {
        $heard = [];
        $client = new Client(
            self::connection("%xt%server%-1%Profanity filter On.%\0", "unparsable\0", "<cross-domain-policy/>\0"),
            static function (Message $message) use (&$heard): void {
                $heard[] = $message->raw;
            },
        );

        $result = $client->run(self::script(
            static fn(): Generator => yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5)),
        ));

        static::assertInstanceOf(ConnectionEstablishedEvent::class, $result);
        static::assertSame(['%xt%server%-1%Profanity filter On.%', '<cross-domain-policy/>'], $heard);
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
        $heard = [];
        $client = new Client(
            self::connection("%xt%server%-1%Profanity filter On.%\0"),
            static function (Message $message) use (&$heard): void {
                $heard[] = $message->raw;
            },
        );

        $answers = $client->run(self::script(static function (): Generator {
            $paused = yield new Pause(Duration::milliseconds(1));
            yield new Pause(Duration::zero());
            $sent = yield new Send(new LogoutCommand());
            $unknown = yield new class() implements Instruction {};

            return [$paused, $sent, $unknown];
        }));

        static::assertSame([null, null, null], $answers);
        static::assertSame(['%xt%server%-1%Profanity filter On.%'], $heard);
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

    #[Test]
    public function it_tells_the_observer_about_every_step(): void
    {
        $steps = [];
        $client = new Client(self::connection(null, "<cross-domain-policy/>\0"), null, static function (
            Instruction $instruction,
            mixed $answer,
        ) use (&$steps): void {
            $steps[] = [$instruction::class, is_object($answer) ? $answer::class : $answer];
        });

        $client->run(self::script(static function (): Generator {
            yield new Pause(Duration::milliseconds(1));
            yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5));
        }));

        static::assertSame(
            [
                [Pause::class, null],
                [Await::class, ConnectionEstablishedEvent::class],
            ],
            $steps,
        );
    }

    #[Test]
    public function it_stops_when_asked_to(): void
    {
        $stopped = new SignalCancellationToken();
        $stopped->cancel();
        $before = new Client(self::connection())->run(
            self::script(static fn(): Generator => yield new Pause(Duration::minutes(1))),
            $stopped,
        );

        $stop = new SignalCancellationToken();
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream
            ->method('read')
            ->willReturnCallback(static function () use ($stop): never {
                $stop->cancel();

                throw new CancelledException($stop);
            });
        $stream->method('reachedEndOfDataSource')->willReturn(false);
        $waiting = new Client(new Connection($stream))->run(
            self::script(
                static fn(): Generator => yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5)),
            ),
            $stop,
        );

        $halt = new SignalCancellationToken();
        $quiet = static::createStub(TCP\StreamInterface::class);
        $quiet
            ->method('read')
            ->willReturnCallback(static function () use ($halt): never {
                $halt->cancel();

                throw new CancelledException($halt);
            });
        $quiet->method('reachedEndOfDataSource')->willReturn(false);
        $pausing = new Client(new Connection($quiet))->run(
            self::script(static fn(): Generator => yield new Pause(Duration::minutes(1))),
            $halt,
        );

        static::assertEquals(new Stopped(), $before);
        static::assertEquals(new Stopped(), $waiting);
        static::assertEquals(new Stopped(), $pausing);
    }

    #[Test]
    public function it_lets_other_cancellations_through(): void
    {
        $this->expectException(CancelledException::class);

        new Client(self::connection())->run(self::script(static function (): Generator {
            yield new Pause(Duration::milliseconds(1));

            throw new CancelledException(new NullCancellationToken());
        }));
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

    private static function connection(?string ...$reads): Connection
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream
            ->method('read')
            ->willReturnCallback(static function (
                ?int $maxBytes = null,
                CancellationTokenInterface $cancellation = new NullCancellationToken(),
            ) use (&$reads): string {
                $next = array_shift($reads);
                if ($next === null) {
                    throw new CancelledException($cancellation);
                }

                return $next;
            });
        $stream->method('reachedEndOfDataSource')->willReturn(false);

        return new Connection($stream);
    }
}
