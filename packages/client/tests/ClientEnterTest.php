<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Client;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\TCP;

final class ClientEnterTest extends TestCase
{
    private const string POLICY = '<cross-domain-policy><allow-access-from domain="*" to-ports="*" /></cross-domain-policy>';

    private const string LOGIN = '%xt%loginResponse%-1%true%93426%made2903%Welcome!%2026-10-15T08:51:16%sNews=1189,sMap=news/Map-UI_r38.swf%';

    private const string REFUSED = '%xt%loginResponse%-1%0%-1%%Your game client is an older version. Server requires AQW game version 5.1 or higher. Please close the AQW game window and try again.%';

    private const string ARRIVAL = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":1350,"areaName":"battleon-2","intType":"2","sExtra":"","strMapName":"battleon","strMapFileName":"Battleon/town-Battleon-9Oct26.swf","uoBranch":[{"uoName":"hero","entType":"p","entID":106762,"strUsername":"Hero","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4185,"intMP":100,"intHPMax":4185,"intMPMax":100,"tx":0,"ty":0,"afk":true,"showCloak":true,"showHelm":true},{"uoName":"made2903","entType":"p","entID":93426,"strUsername":"made2903","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":2585,"intMP":100,"intHPMax":2585,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":true,"showHelm":true}],"monBranch":[]}}}
        JSON;

    private const string INVENTORY = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":15651,"sName":"Healer","iStk":1,"bUpg":false,"sType":"Class","sES":"ar","CharItemID":695648125,"iQty":177790,"bEquip":false}]}}}
        JSON;

    private const string CLASS_CHANGE = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"updateClass","uid":93426,"sClassName":"Blaze Binder","sClassCat":"C1","iCP":302500,"sDesc":"Blaze Binders control the power of fire and darkness. They are known to burn their enemies to a crisp and bind their souls into service","aMRM":["Blaze Binder gain mana from all hits landed in combat, and especially on crits.  The amount depends on damage relative to *their own* HP total."]}}}
        JSON;

    private const string SKILLS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":89,"nam":"Auto Attack","typ":"aa","ref":"aa","tgt":"h","fx":"m","damage":0.9,"range":301,"mp":0,"cd":2000,"auto":true,"isOK":true},{"id":115,"nam":"Fireball","typ":"m","ref":"a1","tgt":"h","tgtMax":1,"mp":20,"cd":6000,"isOK":true},{"id":116,"nam":"Ice Shard","typ":"m","ref":"a2","tgt":"h","mp":15,"cd":6000,"isOK":false},{"id":118,"nam":"Arcane Shield","typ":"m","ref":"a4","tgt":"s","mp":0,"cd":20000,"isOK":false},{"nam":"Potions","typ":"i","ref":"i1","tgt":"f","mp":0,"cd":60000,"isOK":true}],"passive":[{"nam":"Hot Headed","ref":"p1"}]}}}}
        JSON;

    private const string VITALS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"made2903","o":{"intHPMax":2585,"intMPMax":100,"intHP":2585,"intMP":100}}}}
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
            ),
            self::login(),
        );

        static::assertInstanceOf(Client::class, $client);
        static::assertSame(
            [
                "<policy-file-request/>\0",
                "<msg t='sys'><body action='login' r='0'><login z='zone_master'><nick><![CDATA[SPIDER#0001~made2903~5.1]]></nick><pword><![CDATA[TOKEN]]></pword></login></body></msg>\0",
                "%xt%zm%firstJoin%1%\0",
                "%xt%zm%retrieveInventory%1350%93426%\0",
            ],
            $sent,
        );

        $me = $client->character();
        $player = $me->player();
        static::assertSame('made2903', $player->name->value);
        static::assertSame(100, $player->level->value);
        static::assertSame([2585, 2585, 100, 100], [
            $player->vitals->health->current->value,
            $player->vitals->health->maximum->value,
            $player->vitals->mana->current->value,
            $player->vitals->mana->maximum->value,
        ]);
        static::assertSame('Blaze Binder', $player->class->class->name->value);
        static::assertSame(ClassCategory::Caster, $player->class->class->category);
        static::assertSame(302_500, $player->class->points->value);
        static::assertSame(10, $player->class->rank()->value);
        static::assertCount(5, $player->class->class->skills);
        static::assertSame(
            [SkillSlot::AutoAttack, SkillSlot::First, SkillSlot::Potion],
            array_values(array_map(
                static fn(PlayerSkill $held): SkillSlot => $held->skill->slot,
                array_filter($player->skills, static fn(PlayerSkill $held): bool => $held->unlocked),
            )),
        );
        static::assertInstanceOf(ClassItem::class, $player->inventory->find(new ItemId(15_651))->unwrap()->item);

        static::assertSame(93_426, $me->socketId()->value);
        static::assertSame(1350, $me->location()->area->value);
        static::assertSame('battleon', $me->location()->map->name->value);
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
                self::CLASS_CHANGE,
                self::INVENTORY,
            ),
            self::login(),
        );

        static::assertInstanceOf(Client::class, $client);
        static::assertSame('Blaze Binder', $client->character()->player()->class->class->name->value);
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
            'no answer while waiting for ClassChangedEvent, SkillsLoadedEvent, PlayerVitalsUpdatedEvent',
            Outcome::of($result)->summary,
        );
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
