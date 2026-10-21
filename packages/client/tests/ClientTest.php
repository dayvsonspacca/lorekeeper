<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Command\Session\PolicyFileRequestCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;
use RuntimeException;

/** @mago-expect lint:too-many-methods */
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

        $result = new Client(new Connection($stream), self::me())->run(self::script(
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
    public function it_skips_what_it_was_not_waiting_for_and_tells_subscribers_every_event(): void
    {
        $heard = [];
        $client = new Client(
            self::connection("%xt%server%-1%Profanity filter On.%\0", "unparsable\0", "<cross-domain-policy/>\0"),
            self::me(),
        );
        $client->subscribe(static function (Event $event) use (&$heard): void {
            $heard[] = $event->name();
        });

        $result = $client->run(self::script(
            static fn(): Generator => yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5)),
        ));

        static::assertInstanceOf(ConnectionEstablishedEvent::class, $result);
        static::assertSame(['ConnectionEstablished'], $heard);
    }

    #[Test]
    public function it_tells_the_script_when_the_wait_is_over(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willThrowException(new CancelledException(new NullCancellationToken()));
        $stream->method('reachedEndOfDataSource')->willReturn(false);
        $client = new Client(new Connection($stream), self::me());

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
        $client = new Client(self::connection("%xt%server%-1%Profanity filter On.%\0"), self::me());

        $answers = $client->run(self::script(static function (): Generator {
            $paused = yield new Pause(Duration::milliseconds(1));
            yield new Pause(Duration::zero());
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

        $result = new Client(new Connection($stream), self::me())->run(self::script(static function (): Generator {
            yield new Await([ConnectionEstablishedEvent::class], Duration::seconds(5));

            return 'never';
        }));

        static::assertEquals(new Disconnected('The server closed the connection.'), $result);
    }

    #[Test]
    public function a_message_it_cannot_read_fails_the_script_with_the_reason(): void
    {
        $arena = '{"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":732692,"areaName":"icestormarena-48213","intType":"1","sExtra":"NoAfk","strMapName":"icestormarena","strMapFileName":"Icewing/town-icestormarena-21Jul23.swf","monmap":[{"MonMapID":1,"MonID":3984,"strFrame":"r4","bRed":false,"intRSS":-1}],"mondef":[{"sRace":"Undead","MonID":3984,"intLevel":300,"strLinkage":"UndeadMage1","strMonName":"Skeletal Ice Mage","strMonFileName":"UndeadMage1.swf","strBehave":"walk"}],"uoBranch":[],"monBranch":[{"MonMapID":1,"MonID":3984,"intState":1,"intHP":730,"intMP":100,"intHPMax":730,"intMPMax":100,"bRed":false,"iLvl":5,"wDPS":5}]}}}';

        $result = new Client(
            self::connection("{$arena}\0"),
            self::me(),
        )->run(self::script(static function (): Generator {
            yield new Pause(Duration::milliseconds(1));

            return 'never';
        }));

        static::assertInstanceOf(Failed::class, $result);
        static::assertStringStartsWith('Malformed ' . MovedToMapEvent::class, $result->reason);
    }

    #[Test]
    public function a_script_that_breaks_fails_instead_of_taking_the_client_down(): void
    {
        $result = new Client(self::connection(), self::me())->run(self::script(static function (): Generator {
            yield new Pause(Duration::milliseconds(1));

            throw new RuntimeException('Could not join boxes: no answer');
        }));

        static::assertEquals(new Failed('Could not join boxes: no answer'), $result);
    }

    #[Test]
    public function it_stops_when_asked_to(): void
    {
        $stopped = new SignalCancellationToken();
        $stopped->cancel();
        $before = new Client(self::connection(), self::me())->run(
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
        $waiting = new Client(new Connection($stream), self::me())->run(
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
        $pausing = new Client(new Connection($quiet), self::me())->run(
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

        new Client(self::connection(), self::me())->run(self::script(static function (): Generator {
            yield new Pause(Duration::milliseconds(1));

            throw new CancelledException(new NullCancellationToken());
        }));
    }

    #[Test]
    public function waiting_returns_a_disconnection_when_the_server_hangs_up(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturn('');
        $stream->method('reachedEndOfDataSource')->willReturn(true);

        static::assertEquals(
            new Disconnected('The server closed the connection.'),
            new Client(new Connection($stream), self::me())->wait(Duration::seconds(5)),
        );
    }

    #[Test]
    public function waiting_stops_when_asked_to(): void
    {
        $stop = new SignalCancellationToken();
        $stop->cancel();

        static::assertEquals(
            new Stopped(),
            new Client(self::connection(), self::me())->wait(Duration::seconds(5), $stop),
        );
    }

    #[Test]
    public function waiting_lets_other_cancellations_through(): void
    {
        $client = new Client(
            self::connection(
                "{\"t\":\"xt\",\"b\":{\"r\":-1,\"o\":{\"cmd\":\"uotls\",\"unm\":\"lorekeeper03\",\"o\":{\"intState\":0}}}}\0",
            ),
            self::me(),
        );
        $client->subscribe(static function (): never {
            throw new CancelledException(new NullCancellationToken());
        });

        $this->expectException(CancelledException::class);

        $client->wait(Duration::seconds(5));
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

            public function title(): string
            {
                return 'Steps';
            }

            public function run(Character $me): Generator
            {
                return ($this->steps)();
            }
        };
    }

    private static function me(): Character
    {
        return Fixture::me();
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
