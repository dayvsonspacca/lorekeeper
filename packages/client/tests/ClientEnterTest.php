<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Client\Session\ClientVersion;
use Lorekeeper\Client\Session\GameLogin;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\GameSetting;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\TCP;

/** @mago-expect lint:too-many-methods */
final class ClientEnterTest extends TestCase
{
    private const string POLICY = "<cross-domain-policy><allow-access-from domain='*' to-ports='*' /></cross-domain-policy>";

    private const string LOGIN = '%xt%loginResponse%-1%true%45466%made2903%Welcome!%2026-10-19T15:34:06%sNews=1191,sMap=news/Map-UI_r38.swf,sBook=news/spiderbook3.swf,sAssets=Assets_20260731.swf,gMenu=dynamic-gameMenu-17Jan22.swf,sVersion=R0048,QSInfo=519,iMaxBagSlots=525,iMaxBankSlots=950,iMaxHouseSlots=325,iMaxGuildMembers=800,iMaxFriends=300,iMaxLoadoutSlots=50%5.1%';

    private const string REFUSED = '%xt%loginResponse%-1%0%-1%%Your game client is an older version. Server requires AQW game version 5.1 or higher. Please close the AQW game window and try again.%';

    private const string ARRIVAL = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":65897,"areaName":"battleon-3","intType":"2","sExtra":"","strMapName":"battleon","strMapFileName":"Battleon/town-Battleon-16Oct26.swf","uoBranch":[{"uoName":"made2903","entType":"p","entID":45466,"strUsername":"made2903","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":81,"intHP":500,"intMP":100,"intHPMax":500,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":true,"showHelm":true}],"monBranch":[]}}}
        JSON;

    private const string INVENTORY = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":15651,"sName":"Healer","iLvl":1,"iStk":1,"bUpg":false,"sType":"Class","iQty":177790,"bTemp":false},{"ItemID":4723,"sName":"Unidentified 10","iLvl":1,"iStk":1000,"bUpg":false,"sType":"Item","iQty":212,"bTemp":false}]}}}
        JSON;

    private const string CLASS_CHANGE = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"updateClass","uid":45466,"sClassName":"Blaze Binder","sClassCat":"C1","iCP":302500,"sDesc":"Blaze Binders control the power of fire and darkness. They are known to burn their enemies to a crisp and bind their souls into service"}}}
        JSON;

    private const string SKILLS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":1354,"nam":"Fire Bolt","typ":"aa","ref":"aa","tgt":"h","tgtMax":3,"mp":0,"cd":2000,"isOK":true},{"id":848,"nam":"Combustion Blaze","typ":"m","ref":"a1","tgt":"h","tgtMax":3,"mp":10,"cd":4000,"isOK":true},{"id":849,"nam":"Choking Shadow Smoke","typ":"m","ref":"a2","tgt":"h","tgtMax":3,"mp":25,"cd":8000,"isOK":true},{"id":850,"nam":"Fire in Your Skin","typ":"m","ref":"a3","tgt":"s","tgtMax":1,"mp":30,"cd":16000,"isOK":true},{"id":851,"nam":"Dark Fire","typ":"m","ref":"a4","tgt":"h","tgtMax":3,"mp":25,"cd":12000,"isOK":true},{"nam":"Potions","typ":"i","ref":"i1","tgt":"f","mp":0,"cd":60000,"isOK":true}]}}}}
        JSON;

    private const string VITALS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"made2903","o":{"intHPMax":2585,"intMPMax":100,"intHP":2585,"intMP":100}}}}
        JSON;

    private const string STATS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"stu","sta":{"_STR":1,"$STR":3,"_END":4,"$END":7,"_DEX":2,"$DEX":3,"_INT":5,"$INT":6,"_WIS":2,"$WIS":3,"_LCK":2,"$LCK":2,"$dsh":50,"$ap":6,"$sp":13,"$tdo":0.0541,"$tcr":0.0613,"$thi":0.0056,"$tha":0.0226,"$cpo":1,"$cpi":1,"$cao":1,"$cai":1,"$cmo":1.0423,"$cmi":0.9577,"$cdo":1,"$cdi":1,"$cho":1,"$chi":1,"$cmc":1,"$scm":1.5705,"$shb":0,"$smb":0},"wDPS":16}}}
        JSON;

    private const string PLAYER_DATA = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":45466,"data":{"strUsername":"made2903","intLevel":81,"intExp":4514754,"intExpToLevel":5280000,"intGold":6800132,"intCoins":2899,"iUpg":0,"iUpgDays":-5772,"iBagSlots":131,"ia1":10128,"id0":0,"id1":0,"id2":64,"id3":0,"id4":0,"id5":0,"id6":0,"id7":0,"iw0":0,"im0":0}}]}}}
        JSON;

    #[Test]
    public function it_enters_the_game_and_puts_together_the_player_and_their_character(): void
    {
        $sent = [];
        $client = Client::enter(
            self::connection(
                $sent,
                self::POLICY,
                self::LOGIN,
                self::ARRIVAL,
                self::INVENTORY,
                self::CLASS_CHANGE,
                self::SKILLS,
                self::VITALS,
                self::STATS,
                self::PLAYER_DATA,
            ),
            self::login(),
        );

        static::assertInstanceOf(Client::class, $client);
        static::assertSame(
            [
                "<policy-file-request/>\0",
                "<msg t='sys'><body action='login' r='0'><login z='zone_master'><nick><![CDATA[SPIDER#0001~made2903~5.1]]></nick><pword><![CDATA[TOKEN]]></pword></login></body></msg>\0",
                "%xt%zm%firstJoin%1%\0",
                "%xt%zm%retrieveInventory%65897%45466%\0",
                "%xt%zm%retrieveUserDatas%65897%45466%\0",
            ],
            $sent,
        );

        $me = $client->character();
        $player = $me->player();
        static::assertSame('made2903', $player->name->value);
        static::assertSame([81, 4_514_754, 5_280_000], [
            $player->progress->level->value,
            $player->progress->experience->current->value,
            $player->progress->experience->maximum->value,
        ]);
        static::assertSame([6_800_132, 2899], [$player->wallet->gold->value, $player->wallet->coins->value]);
        static::assertFalse($player->tags->has(PlayerTag::Member));
        static::assertSame(131, $player->inventory->slots->value);
        static::assertTrue($player->marks->has(new QuestMark(QuestPeriod::Day, 2, 6)));
        static::assertFalse($player->settings?->allows(GameSetting::Goto));
        static::assertSame([2585, 2585, 100, 100], [
            $player->vitals->health->current->value,
            $player->vitals->health->maximum->value,
            $player->vitals->mana->current->value,
            $player->vitals->mana->maximum->value,
        ]);
        static::assertSame(13, $player->stats->powers->spell->value);
        static::assertEqualsWithDelta(0.0226, $player->stats->haste->value, 1e-9);
        static::assertEqualsWithDelta(0.0423, $player->stats->resistances->magical->value, 1e-9);
        static::assertSame('Blaze Binder', $player->class->class->name->value);
        static::assertSame(ClassCategory::Caster, $player->class->class->category);
        static::assertSame(302_500, $player->class->points->value);
        static::assertSame(10, $player->class->rank()->value);
        static::assertCount(6, $player->class->class->skills);
        static::assertSame(
            [
                SkillSlot::AutoAttack,
                SkillSlot::First,
                SkillSlot::Second,
                SkillSlot::Third,
                SkillSlot::Fourth,
                SkillSlot::Potion,
            ],
            array_values(array_map(
                static fn(PlayerSkill $held): SkillSlot => $held->skill->slot,
                array_filter($player->class->skills, static fn(PlayerSkill $held): bool => $held->unlocked),
            )),
        );
        static::assertInstanceOf(ClassItem::class, $player->inventory->find(new ItemId(15_651))->unwrap()->item);

        static::assertSame(45_466, $me->socketId()->value);
        static::assertSame(65_897, $me->location()->area->value);
        static::assertSame('battleon', $me->location()->map->name->value);
        static::assertSame(3, $me->location()->room->value);
        static::assertSame('Enter', $me->location()->spot->cell->value);
        static::assertSame(MapPad::Spawn, $me->location()->spot->pad);
        static::assertSame(CombatState::Idle, $me->state());
        static::assertFalse($me->away());
    }

    #[Test]
    public function it_does_not_mind_the_order_the_rest_of_the_player_arrives_in(): void
    {
        $sent = [];
        $client = Client::enter(
            self::connection(
                $sent,
                self::POLICY,
                self::LOGIN,
                self::ARRIVAL,
                self::VITALS,
                self::SKILLS,
                self::PLAYER_DATA,
                self::CLASS_CHANGE,
                self::INVENTORY,
            ),
            self::login(),
        );

        static::assertInstanceOf(Client::class, $client);
        static::assertSame('Blaze Binder', $client->character()->player()->class->class->name->value);
        static::assertSame(0.0, $client->character()->player()->stats->haste->value);
    }

    #[Test]
    public function it_stops_when_the_login_is_refused(): void
    {
        $sent = [];

        static::assertInstanceOf(LoginFailedEvent::class, Client::enter(
            self::connection($sent, self::POLICY, self::REFUSED),
            self::login(),
        ));
    }

    #[Test]
    public function it_says_what_never_arrived(): void
    {
        $sent = [];
        $result = Client::enter(
            self::connection($sent, self::POLICY, self::LOGIN, self::ARRIVAL, self::INVENTORY),
            self::login(),
        );

        static::assertInstanceOf(TimedOut::class, $result);
        static::assertSame(
            'no answer while waiting for PlayerDataLoadedEvent, ClassChangedEvent, SkillsLoadedEvent, PlayerVitalsUpdatedEvent',
            Outcome::of($result)->summary,
        );
    }

    #[Test]
    public function it_says_when_the_server_never_answers_the_policy_request(): void
    {
        $sent = [];
        $result = Client::enter(self::connection($sent), self::login());

        static::assertInstanceOf(TimedOut::class, $result);
        static::assertSame('no answer while waiting for ConnectionEstablishedEvent', Outcome::of($result)->summary);
    }

    #[Test]
    public function it_says_when_it_never_arrives_on_a_map(): void
    {
        $sent = [];
        $result = Client::enter(self::connection($sent, self::POLICY, self::LOGIN), self::login());

        static::assertInstanceOf(TimedOut::class, $result);
        static::assertSame('no answer while waiting for MovedToMapEvent', Outcome::of($result)->summary);
    }

    #[Test]
    public function it_returns_a_disconnection_when_the_server_hangs_up_while_entering(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturn('');
        $stream->method('reachedEndOfDataSource')->willReturn(true);

        static::assertEquals(
            new Disconnected('The server closed the connection.'),
            Client::enter(new Connection($stream), self::login()),
        );
    }

    #[Test]
    public function it_stops_entering_when_asked_to(): void
    {
        $sent = [];
        $stop = new SignalCancellationToken();
        $stop->cancel();

        static::assertEquals(new Stopped(), Client::enter(self::connection($sent, self::POLICY), self::login(), $stop));
    }

    #[Test]
    public function it_lets_other_cancellations_through_while_entering(): void
    {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('writeAll')->willThrowException(new CancelledException(new NullCancellationToken()));

        $this->expectException(CancelledException::class);

        Client::enter(new Connection($stream), self::login());
    }

    private static function login(): GameLogin
    {
        return new GameLogin(new PlayerName('made2903'), 'TOKEN', new ClientVersion('5.1'));
    }

    /** @param list<string> $sent */
    private static function connection(array &$sent, string ...$messages): Connection
    {
        $reads = array_map(static fn(string $message): string => $message . "\0", array_values($messages));
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream
            ->method('writeAll')
            ->willReturnCallback(static function (string $bytes) use (&$sent): void {
                $sent[] = $bytes;
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
