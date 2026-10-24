<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use Lorekeeper\Client\Api\Login\LoginClient;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\OutcomeStatus;
use Lorekeeper\Client\Session\ClientVersion;
use Lorekeeper\Client\Session\Credentials;
use Lorekeeper\Client\Session\Ending;
use Lorekeeper\Client\Session\EndingStatus;
use Lorekeeper\Client\Session\InMemoryVersions;
use Lorekeeper\Client\Session\Login;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\IO;
use Psl\TCP;

/** @mago-expect lint:too-many-methods */
final class LoginTest extends TestCase
{
    private const string POLICY = "<cross-domain-policy/>\0";

    private const string LOGGED_IN = "%xt%loginResponse%-1%true%59878%Hero%Welcome!%\0";

    private const string INVENTORY = "{\"b\":{\"o\":{\"cmd\":\"loadInventoryBig\",\"items\":[]}}}\0";

    private const string CLASS_CHANGE = "{\"b\":{\"o\":{\"cmd\":\"updateClass\",\"uid\":59878,\"sClassName\":\"Mage\",\"sClassCat\":\"C1\",\"iCP\":0}}}\0";

    private const string SKILLS = "{\"b\":{\"o\":{\"cmd\":\"sAct\",\"actions\":{\"active\":[{\"ref\":\"aa\",\"nam\":\"Auto Attack\",\"tgt\":\"h\",\"mp\":0,\"cd\":2000,\"isOK\":true}]}}}}\0";

    private const string VITALS = "{\"b\":{\"o\":{\"cmd\":\"uotls\",\"unm\":\"Hero\",\"o\":{\"intHP\":390,\"intHPMax\":390,\"intMP\":100,\"intMPMax\":100}}}}\0";

    private const string PLAYER_DATA = "{\"b\":{\"o\":{\"cmd\":\"initUserDatas\",\"a\":[{\"uid\":59878,\"data\":{\"strUsername\":\"Hero\",\"intLevel\":1,\"intExp\":0,\"intExpToLevel\":100,\"intGold\":0,\"intCoins\":0,\"iUpg\":0,\"iUpgDays\":-5772,\"iBagSlots\":30,\"iBankSlots\":30}}]}}}\0";

    #[Test]
    public function it_enters_the_game_and_hands_back_a_client_that_plays_scripts(): void
    {
        $sent = [];
        $client = self::enter($sent, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1350),
            self::INVENTORY,
            self::CLASS_CHANGE,
            self::SKILLS,
            self::VITALS,
            self::PLAYER_DATA,
            self::moved('forest', 7),
        ]);
        $heard = [];
        $client->subscribe(static function (Event $event) use (&$heard): void {
            $heard[] = $event->name();
        });

        $went = Outcome::of($client->run(new GoToForest()));
        $again = Outcome::of($client->run(new GoToForest()));
        $loggedOut = Login::leave($client);

        static::assertEquals(new Outcome(OutcomeStatus::Done, 'Now in forest'), $went);
        static::assertEquals(new Outcome(OutcomeStatus::Done, 'Now in forest'), $again);
        static::assertTrue($loggedOut);
        static::assertSame(['MovedToMap'], $heard);
        static::assertStringContainsString('SPIDER#0001~Hero~5.3]]>', $sent[1]);
        static::assertSame('%xt%zm%cmd%1%tfer%Hero%forest%', $sent[5]);
        static::assertSame('%xt%zm%logout%1%', end($sent));
        static::assertSame('forest', $client->character()->location()->map->name->value);
    }

    #[Test]
    public function a_client_in_the_game_waits_and_a_cancelled_script_ends_as_cancelled(): void
    {
        $sent = [];
        $client = self::enter($sent, [
            self::POLICY,
            self::LOGGED_IN,
            self::moved('battleon', 1),
            self::INVENTORY,
            self::CLASS_CHANGE,
            self::SKILLS,
            self::VITALS,
            self::PLAYER_DATA,
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
    public function it_does_not_enter_when_the_server_refuses_the_login(): void
    {
        $sent = [];
        $connection = self::connection($sent, [self::POLICY, "%xt%loginResponse%-1%0%-1%%Nope%\0"]);

        $ending = Login::enterOn($connection, new PlayerName('Hero'), 'TOKEN');

        static::assertEquals(new Ending(EndingStatus::Failed, 'Login refused: Nope'), $ending);
    }

    #[Test]
    public function it_ends_as_stopped_when_asked_to_stop_while_entering(): void
    {
        $sent = [];
        $stop = new SignalCancellationToken();
        $stop->cancel();

        $ending = Login::enterOn(self::connection($sent, []), new PlayerName('Hero'), 'TOKEN', $stop);

        static::assertEquals(new Ending(EndingStatus::Stopped), $ending);
    }

    #[Test]
    public function it_logs_in_picks_the_server_and_ends_when_the_server_hangs_up(): void
    {
        $listener = TCP\listen('127.0.0.1');
        $port = $listener->getLocalAddress()->port;
        $hangUp = Async\run(static function () use ($listener): void {
            $listener->accept()->close();
        });
        $response = self::loginResponse(
            '{"login":{"sToken":"TOKEN"},"servers":[{"sName":"Twilly","sIP":"127.0.0.1","iPort":'
            . $port
            . ',"bOnline":1,"bUpg":0}]}',
        );

        try {
            $ending = Login::enter(
                new Credentials(new PlayerName('Hero'), 'secret', 'Twilly'),
                logins: new LoginClient("file://{$response}"),
            );
        } finally {
            unlink($response);
            $hangUp->await();
            $listener->close();
        }

        static::assertInstanceOf(Ending::class, $ending);
        static::assertSame(EndingStatus::Disconnected, $ending->status);
    }

    #[Test]
    public function it_learns_the_version_the_server_demands_and_tries_again_once(): void
    {
        $listener = TCP\listen('127.0.0.1');
        $port = $listener->getLocalAddress()->port;
        $nicks = [];
        $server = Async\run(static function () use ($listener, &$nicks): void {
            foreach ([
                'Server requires AQW game version 5.4 or higher.',
                'Server requires AQW game version 5.4 or higher.',
            ] as $refusal) {
                $connection = $listener->accept();
                self::readUntilNull($connection);
                $connection->writeAll(self::POLICY);
                preg_match('/SPIDER#0001~[^~]+~([^\]]+)/', self::readUntilNull($connection), $nick);
                $nicks[] = $nick[1] ?? '';
                $connection->writeAll("%xt%loginResponse%-1%0%-1%%{$refusal}%\0");
                $connection->close();
            }
        });
        $response = self::loginResponse(
            '{"login":{"sToken":"TOKEN"},"servers":[{"sName":"Twilly","sIP":"127.0.0.1","iPort":'
            . $port
            . ',"bOnline":1,"bUpg":0}]}',
        );
        $versions = new InMemoryVersions(new ClientVersion('5.3'));

        try {
            $ending = Login::enter(
                new Credentials(new PlayerName('Hero'), 'secret', 'Twilly'),
                logins: new LoginClient("file://{$response}"),
                versions: $versions,
            );
        } finally {
            unlink($response);
            $server->await();
            $listener->close();
        }

        static::assertSame(['5.3', '5.4'], $nicks);
        static::assertEquals(new ClientVersion('5.4'), $versions->current());
        static::assertEquals(
            new Ending(EndingStatus::Failed, 'Login refused: Server requires AQW game version 5.4 or higher.'),
            $ending,
        );
    }

    #[Test]
    public function it_does_not_enter_when_the_chosen_server_is_not_open(): void
    {
        $response = self::loginResponse(
            '{"login":{"sToken":"TOKEN"},"servers":[{"sName":"Twilly","sIP":"127.0.0.1","iPort":5588,"bOnline":1,"bUpg":0}]}',
        );

        try {
            $ending = Login::enter(
                new Credentials(new PlayerName('Hero'), 'secret', 'Artix'),
                logins: new LoginClient("file://{$response}"),
            );
        } finally {
            unlink($response);
        }

        static::assertEquals(new Ending(EndingStatus::Failed, 'no open server'), $ending);
    }

    #[Test]
    public function it_does_not_enter_when_the_login_api_cannot_be_reached(): void
    {
        $ending = Login::enter(
            new Credentials(new PlayerName('Hero'), 'secret'),
            logins: new LoginClient('http://127.0.0.1:1/'),
        );

        static::assertInstanceOf(Ending::class, $ending);
        static::assertSame(EndingStatus::Failed, $ending->status);
        static::assertStringStartsWith('Could not reach the login API', (string) $ending->reason);
    }

    #[Test]
    public function leaving_a_connection_that_is_already_gone_says_so(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('writeAll')->willThrowException(new IO\Exception\RuntimeException('Broken pipe'));

        static::assertFalse(Login::leave(new Client(new Connection($stream), Fixture::me())));
    }

    private static function readUntilNull(TCP\StreamInterface $connection): string
    {
        $read = '';
        while (!str_contains($read, "\0")) {
            $read .= $connection->read();
        }

        return $read;
    }

    /** @return non-empty-string */
    private static function loginResponse(string $json): string
    {
        $response = tempnam(sys_get_temp_dir(), prefix: 'login');
        file_put_contents($response, data: $json);

        return $response;
    }

    /**
     * @param list<string> $sent
     * @param list<null|string> $reads
     */
    private static function enter(array &$sent, array $reads): Client
    {
        $client = Login::enterOn(self::connection($sent, $reads), new PlayerName('Hero'), 'TOKEN');
        static::assertInstanceOf(Client::class, $client);

        return $client;
    }

    private static function moved(string $map, int $area): string
    {
        return "{\"b\":{\"o\":{\"cmd\":\"moveToArea\",\"areaId\":{$area},\"areaName\":\"{$map}-1\",\"strMapName\":\"{$map}\",\"strMapFileName\":\"{$map}.swf\",\"uoBranch\":[{\"entID\":59878,\"strUsername\":\"Hero\",\"intLevel\":1,\"strFrame\":\"Enter\",\"strPad\":\"Spawn\",\"intState\":1,\"intHP\":390,\"intHPMax\":390,\"intMP\":100,\"intMPMax\":100,\"afk\":false}],\"monBranch\":[]}}}\0";
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
