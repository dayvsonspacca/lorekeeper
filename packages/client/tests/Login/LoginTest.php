<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Login;

use Generator;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Script\BotScript;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Login\Ending;
use Lorekeeper\Client\Login\EndingStatus;
use Lorekeeper\Client\Login\Login;
use Lorekeeper\Client\Narrator;
use Lorekeeper\Client\Observer;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\OutcomeStatus;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Tests\RecordingObserver;
use Lorekeeper\Core\Player\PlayerName;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class LoginTest extends TestCase
{
    private const string POLICY = "<cross-domain-policy/>\0";

    private const string LOGGED_IN = "%xt%loginResponse%-1%true%59878%Hero%Welcome!%\0";

    private const string INVENTORY = "{\"b\":{\"o\":{\"cmd\":\"loadInventoryBig\",\"items\":[]}}}\0";

    #[Test]
    public function it_enters_the_game_and_hands_back_a_client_that_plays_scripts(): void
    {
        $sent = [];
        $observer = new RecordingObserver();
        $client = self::enter($sent, new Narrator($observer), [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1350),
            self::INVENTORY,
            self::moved('forest', 7),
        ]);

        $went = Outcome::of($client->run(new GoToForest()));
        $again = Outcome::of($client->run(new GoToForest()));
        $loggedOut = Login::leave($client);

        static::assertEquals(new Outcome(OutcomeStatus::Done, 'Now in forest'), $went);
        static::assertEquals(new Outcome(OutcomeStatus::Done, 'Now in forest'), $again);
        static::assertTrue($loggedOut);
        static::assertSame(
            [
                'moved battleon 1350',
                'progressed 0/1',
                'moved forest 7',
                'progressed 1/1',
                'progressed 0/1',
                'progressed 1/1',
            ],
            $observer->updates,
        );
        static::assertContains('success: Entered the game in battleon (room 1)', $observer->lines);
        static::assertContains('success: Moved to forest (room 1) · 0 monsters, 0 players', $observer->lines);
        static::assertSame('%xt%zm%cmd%1%tfer%Hero%forest%', $sent[4]);
        static::assertSame('%xt%zm%logout%1%', end($sent));
        static::assertSame('forest', $client->character()->position()->map->value);
    }

    #[Test]
    public function a_client_in_the_game_waits_and_a_cancelled_script_ends_as_cancelled(): void
    {
        $sent = [];
        $client = self::enter($sent, new RecordingObserver(), [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);
        $cancel = new SignalCancellationToken();
        $cancel->cancel();

        static::assertNull($client->wait(Duration::milliseconds(10)));
        static::assertEquals(
            new Outcome(OutcomeStatus::Cancelled, 'cancelled'),
            Outcome::of($client->run(new GoToForest(), $cancel)),
        );
    }

    #[Test]
    public function the_narrator_tells_about_a_long_idle_once(): void
    {
        $sent = [];
        $observer = new RecordingObserver();
        $client = self::enter($sent, new Narrator($observer), [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
        ]);
        $idle = new class() extends BotScript {
            #[Override]
            public function title(): string
            {
                return 'Idle';
            }

            #[Override]
            protected function rules(): Generator
            {
                yield new Pause(Duration::minutes(1));
                yield new Pause(Duration::minutes(1));

                return new Done('Rested');
            }
        };

        $client->run($idle);

        static::assertSame(
            ['info: Idling in battleon'],
            array_values(array_filter($observer->lines, static fn(string $line): bool => str_contains(
                $line,
                'Idling',
            ))),
        );
    }

    #[Test]
    public function it_does_not_enter_when_the_server_refuses_the_login(): void
    {
        $sent = [];
        $connection = self::connection($sent, [self::POLICY, "%xt%loginResponse%-1%0%-1%%Nope%\0"]);

        $ending = Login::enterOn($connection, new PlayerName('Hero'), 'TOKEN', new RecordingObserver());

        static::assertEquals(new Ending(EndingStatus::Failed, 'Login refused'), $ending);
    }

    /**
     * @param list<string> $sent
     * @param list<null|string> $reads
     */
    private static function enter(array &$sent, Observer $observer, array $reads): Client
    {
        $client = Login::enterOn(self::connection($sent, $reads), new PlayerName('Hero'), 'TOKEN', $observer);
        static::assertInstanceOf(Client::class, $client);

        return $client;
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
