<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use LogicException;
use Lorekeeper\Client\MessageParser;
use Lorekeeper\Client\Session\Entrance;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\Hero\HeroSettings;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Player\PlayerSettingsData;
use Lorekeeper\Server\Event\Player\PlayerSettingsLoadedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EntranceTest extends TestCase
{
    private const string LOGIN = '%xt%loginResponse%-1%true%45466%Hero3%Welcome!%2026-10-19T15:34:06%sNews=1191%5.1%';

    private const string ARRIVAL = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":65897,"areaName":"battleon-3","intType":"2","sExtra":"","strMapName":"battleon","strMapFileName":"Battleon/town-Battleon-16Oct26.swf","uoBranch":[{"uoName":"Hero3","entType":"p","entID":45466,"strUsername":"Hero3","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":81,"intHP":500,"intMP":100,"intHPMax":500,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":true,"showHelm":true}],"monBranch":[]}}}
        JSON;

    private const string INVENTORY = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":15651,"sName":"Healer","iLvl":1,"iStk":1,"bUpg":false,"sType":"Class","iQty":177790,"bTemp":false}]}}}
        JSON;

    private const string CLASS_CHANGE = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"updateClass","uid":45466,"sClassName":"Blaze Binder","sClassCat":"C1","iCP":302500}}}
        JSON;

    private const string SKILLS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":1354,"nam":"Fire Bolt","typ":"aa","ref":"aa","tgt":"h","tgtMax":3,"mp":0,"cd":2000,"isOK":true}]}}}}
        JSON;

    private const string VITALS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"Hero3","o":{"intHPMax":2585,"intMPMax":100,"intHP":2585,"intMP":100}}}}
        JSON;

    #[Test]
    public function it_refuses_to_put_the_character_together_before_everything_arrived(): void
    {
        $entrance = self::entrance(self::LOGIN, self::INVENTORY);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Still waiting for ' . MovedToMapEvent::class);

        $entrance->character();
    }

    #[Test]
    public function before_logging_in_it_waits_for_the_login_and_the_map(): void
    {
        $entrance = new Entrance(new PlayerName('Hero3'));
        $entrance->hear(Fixture::arrival('battleon', 1));

        static::assertContains(LoginSucceededEvent::class, $entrance->missing());
        static::assertContains(MovedToMapEvent::class, $entrance->missing());
    }

    #[Test]
    public function a_map_it_is_not_on_does_not_count_as_arriving(): void
    {
        $entrance = self::entrance(self::LOGIN);
        $entrance->hear(Fixture::arrival('battleon', 1));

        static::assertContains(MovedToMapEvent::class, $entrance->missing());
    }

    #[Test]
    public function it_keeps_its_own_data_and_tags_a_member(): void
    {
        $entrance = self::entrance(
            self::LOGIN,
            self::ARRIVAL,
            self::INVENTORY,
            self::CLASS_CHANGE,
            self::SKILLS,
            self::VITALS,
        );
        $entrance->hear(new PlayerDataLoadedEvent([self::member(45_466)]));
        $entrance->hear(new PlayerDataLoadedEvent([self::member(1)]));

        static::assertSame([], $entrance->missing());
        $hero = $entrance->character()->hero();
        static::assertTrue($hero->tags->has(PlayerTag::Member));
        static::assertSame([45_466, 81, 131, 40, 1], [
            $hero->spawnId->value,
            $hero->level->value,
            $hero->inventory->slots->value,
            $hero->inventory->bank->slots->value,
            $hero->inventory->quantity(new ItemId(15_651)),
        ]);
        static::assertSame([2585, 'Blaze Binder', 1], [
            $hero->vitals->health->current->value,
            $hero->class->class->name->value,
            count($hero->class->skills),
        ]);
    }

    #[Test]
    public function it_keeps_its_own_settings_and_not_those_of_others_in_the_room(): void
    {
        $entrance = self::entrance(
            self::LOGIN,
            self::ARRIVAL,
            self::INVENTORY,
            self::CLASS_CHANGE,
            self::SKILLS,
            self::VITALS,
        );
        $entrance->hear(new PlayerDataLoadedEvent([self::member(45_466)]));
        $mine = new HeroSettings([GameSetting::Goto]);
        $entrance->hear(new PlayerSettingsLoadedEvent([
            new PlayerSettingsData(new PlayerSpawnId(45_466), new PlayerName('Hero'), $mine),
        ]));
        $entrance->hear(new PlayerSettingsLoadedEvent([
            new PlayerSettingsData(new PlayerSpawnId(1), new PlayerName('Hero2'), new HeroSettings()),
        ]));

        static::assertSame($mine, $entrance->character()->hero()->settings);
    }

    #[Test]
    public function without_settings_in_its_data_it_has_everything_allowed_like_a_new_account(): void
    {
        $entrance = self::entrance(
            self::LOGIN,
            self::ARRIVAL,
            self::INVENTORY,
            self::CLASS_CHANGE,
            self::SKILLS,
            self::VITALS,
        );
        $entrance->hear(new PlayerDataLoadedEvent([self::member(45_466)]));

        $settings = $entrance->character()->hero()->settings;
        foreach (GameSetting::cases() as $setting) {
            static::assertTrue($settings->allows($setting));
        }
    }

    private static function entrance(string ...$messages): Entrance
    {
        $entrance = new Entrance(new PlayerName('Hero3'));
        foreach ($messages as $message) {
            foreach (Events::recognize(MessageParser::parse($message)->unwrap()) as $event) {
                static::assertInstanceOf(Event::class, $event);
                $entrance->hear($event);
            }
        }

        return $entrance;
    }

    private static function member(int $spawnId): PlayerData
    {
        return new PlayerData(
            new PlayerSpawnId($spawnId),
            new PlayerName('Hero3'),
            new PlayerLevel(81),
            new Gauge(new Experience(0), new Experience(1000)),
            new HeroWallet(new Gold(0), new AdventureCoins(0)),
            true,
            new Quantity(131),
            new Quantity(40),
            [],
        );
    }
}
