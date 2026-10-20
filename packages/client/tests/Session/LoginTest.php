<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\OutcomeStatus;
use Lorekeeper\Client\Session\Ending;
use Lorekeeper\Client\Session\EndingStatus;
use Lorekeeper\Client\Session\Login;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
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

    private const string CLASS_CHANGE = "{\"b\":{\"o\":{\"cmd\":\"updateClass\",\"uid\":59878,\"sClassName\":\"Mage\",\"sClassCat\":\"C1\",\"iCP\":0}}}\0";

    private const string SKILLS = "{\"b\":{\"o\":{\"cmd\":\"sAct\",\"actions\":{\"active\":[{\"ref\":\"aa\",\"nam\":\"Auto Attack\",\"tgt\":\"h\",\"mp\":0,\"cd\":2000,\"isOK\":true}]}}}}\0";

    private const string VITALS = "{\"b\":{\"o\":{\"cmd\":\"uotls\",\"unm\":\"Hero\",\"o\":{\"intHP\":390,\"intHPMax\":390,\"intMP\":100,\"intMPMax\":100}}}}\0";

    private const string PLAYER_DATA = "{\"b\":{\"o\":{\"cmd\":\"initUserDatas\",\"a\":[{\"uid\":59878,\"data\":{\"strUsername\":\"Hero\",\"intLevel\":1,\"intExp\":0,\"intExpToLevel\":100,\"intGold\":0,\"intCoins\":0,\"iUpg\":0,\"iUpgDays\":-5772,\"iBagSlots\":30}}]}}}\0";

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

        static::assertEquals(new Ending(EndingStatus::Failed, 'Login refused'), $ending);
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
