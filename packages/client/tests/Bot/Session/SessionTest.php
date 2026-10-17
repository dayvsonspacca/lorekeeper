<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Session;

use Generator;
use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Session\Ending;
use Lorekeeper\Client\Bot\Session\Job;
use Lorekeeper\Client\Bot\Session\Jobs;
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
    public function it_enters_the_game_plays_its_jobs_in_order_and_logs_out_when_stopped(): void
    {
        $stop = new SignalCancellationToken();
        $sent = [];
        $connection = self::connection($sent, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1350),
            self::INVENTORY,
            self::moved('forest', 7),
            null,
        ]);
        $jobs = self::jobs($stop, [
            new Job(1, new GoToMapTask(new MapName('forest'))),
            new Job(2, new AskQuestsTask(new MapName('forest'), [new QuestId(3), new QuestId(5)])),
        ]);
        $observer = new RecordingObserver();

        $ending = self::session($observer)->playOn($connection, 'TOKEN', $jobs, $stop);

        static::assertEquals(new Ending(SessionStatus::Stopped), $ending);
        static::assertSame(
            [
                'moved battleon 1350',
                'started 1',
                'moved forest 7',
                'finished 1 done: Now in forest',
                'started 2',
                'progressed 2 0/1',
                'progressed 2 1/1',
                'finished 2 done: Loaded 0 of 2 quests',
            ],
            $observer->updates,
        );
        static::assertContains('info: Task #1: Go to forest', $observer->lines);
        static::assertContains('success: Task #1 done: Now in forest', $observer->lines);
        static::assertContains('warning: No answer to load quest after 15s', $observer->lines);
        static::assertSame('%xt%zm%getQuests%7%3%5%', $sent[5]);
        static::assertSame('%xt%zm%logout%1%', end($sent));
        static::assertSame(5, $observer->heard);
    }

    #[Test]
    public function it_waits_in_the_game_until_there_is_something_to_do(): void
    {
        $stop = new SignalCancellationToken();
        $sent = [];
        $connection = self::connection($sent, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);
        $observer = new RecordingObserver();

        $ending = self::session($observer)->playOn($connection, 'TOKEN', self::jobs($stop, [null, null]), $stop);

        static::assertSame(SessionStatus::Stopped, $ending->status);
        static::assertSame(1, count(array_filter($observer->lines, static fn(string $line): bool => str_starts_with(
            $line,
            'info: Waiting for tasks',
        ))));
        static::assertContains('info: Waiting for tasks in battleon', $observer->lines);
    }

    #[Test]
    public function it_tells_about_a_long_idle_once(): void
    {
        $stop = new SignalCancellationToken();
        $sent = [];
        $connection = self::connection($sent, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);
        $observer = new RecordingObserver();
        $idle = new class() implements Task {
            #[Override]
            public function title(): string
            {
                return 'Idle';
            }

            #[Override]
            public function script(PlayerName $player, Position $here): Script
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

        self::session($observer)->playOn($connection, 'TOKEN', self::jobs($stop, [new Job(1, $idle)]), $stop);

        static::assertSame(
            ['info: Task #1: Idle', 'info: Idling in battleon', 'success: Task #1 done: Rested'],
            array_values(array_filter(
                $observer->lines,
                static fn(string $line): bool => str_contains($line, 'Task') || str_contains($line, 'Idling'),
            )),
        );
    }

    #[Test]
    public function it_fails_when_the_server_refuses_the_login(): void
    {
        $sent = [];
        $connection = self::connection($sent, [self::POLICY, "%xt%loginResponse%-1%0%-1%%Nope%\0"]);

        $ending = self::session(new RecordingObserver())
            ->playOn($connection, 'TOKEN', self::jobs(new SignalCancellationToken(), []));

        static::assertEquals(new Ending(SessionStatus::Failed, 'Login refused'), $ending);
    }

    private static function session(RecordingObserver $observer): Session
    {
        return new Session(new Credentials(new PlayerName('Hero'), 'secret'), $observer);
    }

    /** @param list<null|Job> $queue */
    private static function jobs(SignalCancellationToken $stop, array $queue): Jobs
    {
        return new class($stop, $queue) implements Jobs {
            /** @param list<null|Job> $queue */
            public function __construct(
                private readonly SignalCancellationToken $stop,
                private array $queue,
            ) {}

            #[Override]
            public function next(): ?Job
            {
                if ($this->queue === []) {
                    $this->stop->cancel();

                    return null;
                }

                return array_shift($this->queue);
            }

            #[Override]
            public function cancelled(Job $job): bool
            {
                return false;
            }
        };
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
