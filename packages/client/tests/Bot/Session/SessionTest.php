<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Session;

use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Session\Ending;
use Lorekeeper\Client\Bot\Session\Outcome;
use Lorekeeper\Client\Bot\Session\OutcomeStatus;
use Lorekeeper\Client\Bot\Session\Session;
use Lorekeeper\Client\Bot\Session\SessionStatus;
use Lorekeeper\Client\Bot\Task\AskQuestsTask;
use Lorekeeper\Client\Bot\Task\GoToMapTask;
use Lorekeeper\Client\Bot\Task\Task;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class SessionTest extends TestCase
{
    private const string POLICY = "<cross-domain-policy/>\0";

    private const string LOGGED_IN = "%xt%loginResponse%-1%true%59878%Hero%Welcome!%\0";

    private const string INVENTORY = "{\"b\":{\"o\":{\"cmd\":\"loadInventoryBig\",\"items\":[]}}}\0";

    #[Test]
    public function it_enters_the_game_plays_what_it_is_given_in_order_and_leaves(): void
    {
        $sent = [];
        $observer = new RecordingObserver();
        $session = self::enter($sent, $observer, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1350),
            self::INVENTORY,
            self::moved('forest', 7),
            null,
        ]);

        $went = $session->play(new GoToMapTask(new MapName('forest')));
        $asked = $session->play(new AskQuestsTask(new MapName('forest'), [new QuestId(3), new QuestId(5)]));
        $session->leave();

        static::assertEquals(new Outcome(OutcomeStatus::Done, 'Now in forest'), $went);
        static::assertEquals(new Outcome(OutcomeStatus::Done, 'Loaded 0 of 2 quests'), $asked);
        static::assertSame(
            ['moved battleon 1350', 'moved forest 7', 'progressed 0/1', 'progressed 1/1'],
            $observer->updates,
        );
        static::assertContains('info: Playing Go to forest', $observer->lines);
        static::assertContains('success: Go to forest: Now in forest', $observer->lines);
        static::assertContains('warning: No answer to load quest after 15s', $observer->lines);
        static::assertSame('%xt%zm%getQuests%7%3%5%', $sent[5]);
        static::assertSame('%xt%zm%logout%1%', end($sent));
        static::assertFalse($session->connected());
        static::assertSame('forest', $session->character()->position()->map->value);
    }

    #[Test]
    public function it_waits_in_the_game_and_stays_connected(): void
    {
        $sent = [];
        $session = self::enter($sent, new RecordingObserver(), [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);

        static::assertTrue($session->wait(Duration::milliseconds(10)));
        static::assertTrue($session->connected());
    }

    #[Test]
    public function a_cancelled_play_ends_as_cancelled(): void
    {
        $sent = [];
        $session = self::enter($sent, new RecordingObserver(), [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);
        $cancel = new SignalCancellationToken();
        $cancel->cancel();

        $outcome = $session->play(new GoToMapTask(new MapName('forest')), $cancel);

        static::assertEquals(new Outcome(OutcomeStatus::Cancelled, 'cancelled'), $outcome);
        static::assertTrue($session->connected());
    }

    #[Test]
    public function it_tells_about_a_long_idle_once(): void
    {
        $sent = [];
        $observer = new RecordingObserver();
        $session = self::enter($sent, $observer, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);
        $idle = new class() implements Task {
            #[Override]
            public function title(): string
            {
                return 'Idle';
            }

            #[Override]
            public function script(Character $me): Script
            {
                return new class() implements Script {
                    #[Override]
                    public function run(): Generator
                    {
                        yield new Pause(Duration::minutes(1));
                        yield new Pause(Duration::minutes(1));

                        return new Done('Rested');
                    }
                };
            }
        };

        $session->play($idle);

        static::assertSame(
            ['info: Playing Idle', 'info: Idling in battleon', 'success: Idle: Rested'],
            array_values(array_filter(
                $observer->lines,
                static fn(string $line): bool => str_contains($line, 'Idle') || str_contains($line, 'Idling'),
            )),
        );
    }

    #[Test]
    public function it_does_not_enter_when_the_server_refuses_the_login(): void
    {
        $sent = [];
        $connection = self::connection($sent, [self::POLICY, "%xt%loginResponse%-1%0%-1%%Nope%\0"]);

        $ending = Session::enterOn($connection, new PlayerName('Hero'), 'TOKEN', new RecordingObserver());

        static::assertEquals(new Ending(SessionStatus::Failed, 'Login refused'), $ending);
    }

    /**
     * @param list<string> $sent
     * @param list<null|string> $reads
     */
    private static function enter(array &$sent, RecordingObserver $observer, array $reads): Session
    {
        $session = Session::enterOn(self::connection($sent, $reads), new PlayerName('Hero'), 'TOKEN', $observer);
        static::assertInstanceOf(Session::class, $session);

        return $session;
    }

    private static function moved(string $map, int $area): string
    {
        return "{\"b\":{\"o\":{\"cmd\":\"moveToArea\",\"areaId\":{$area},\"areaName\":\"{$map}-1\",\"strMapName\":\"{$map}\",\"strMapFileName\":\"{$map}.swf\",\"uoBranch\":[],\"monBranch\":[]}}}\0";
    }

    /**
     * @param list<string> $sent
     * @param list<null|string> $reads
     */
    private static function connection(array &$sent, array $reads): Connection
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream
            ->method('writeAll')
            ->willReturnCallback(static function (string $bytes) use (&$sent): void {
                $sent[] = rtrim($bytes, characters: "\0");
            });
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
