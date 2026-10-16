<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Mention\Mention;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class MovedToMapEventTest extends TestCase
{
    private const string FOREST = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":687803,"areaName":"forest-1","intType":"1","sExtra":"","strMapName":"forest","strMapFileName":"GreenGuard/town-Forest-13Aug24.swf","monmap":[{"MonMapID":1,"MonID":32,"strFrame":"Forest1","bRed":false,"intRSS":-1},{"MonMapID":2,"MonID":32,"strFrame":"Forest1","bRed":false,"intRSS":-1},{"MonMapID":3,"MonID":32,"strFrame":"Forest2","bRed":false,"intRSS":-1},{"MonMapID":4,"MonID":1,"strFrame":"Forest2","bRed":false,"intRSS":-1},{"MonMapID":5,"MonID":1,"strFrame":"Forest3","bRed":false,"intRSS":-1},{"MonMapID":6,"MonID":1,"strFrame":"Forest3","bRed":false,"intRSS":-1},{"MonMapID":7,"MonID":1,"strFrame":"Forest3","bRed":false,"intRSS":-1},{"MonMapID":8,"MonID":2,"strFrame":"End","bRed":false,"intRSS":-1}],"mondef":[{"sRace":"None","MonID":1,"intLevel":3,"strLinkage":"Zardman","strMonName":"Zardman Grunt","strMonFileName":"Zardman.swf","strBehave":"walk"},{"sRace":"None","MonID":2,"intLevel":3,"strLinkage":"ZardmanBoss","strMonName":"Boss Zardman","strMonFileName":"ZardmanBoss.swf","strBehave":"walk"},{"sRace":"None","MonID":32,"intLevel":3,"strLinkage":"Zardman2","strMonName":"Zardman Hunter","strMonFileName":"Zardman2.swf","strBehave":"walk"}],"uoBranch":[{"uoName":"hero","entType":"p","entID":116500,"strUsername":"Hero","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":81,"intHP":2585,"intMP":100,"intHPMax":2585,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":true,"showHelm":true}],"monBranch":[{"MonMapID":1,"MonID":32,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":2,"MonID":32,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":3,"MonID":32,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":4,"MonID":1,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":5,"MonID":1,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":6,"MonID":1,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":7,"MonID":1,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":8,"MonID":2,"intState":1,"intHP":970,"intMP":100,"intHPMax":970,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":2}]}}}
        JSON;

    private const string BATTLEON = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":1350,"areaName":"battleon-2","intType":"2","sExtra":"","strMapName":"battleon","strMapFileName":"Battleon/town-Battleon-9Oct26.swf","uoBranch":[{"uoName":"hero","entType":"p","entID":106762,"strUsername":"Hero","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4185,"intMP":100,"intHPMax":4185,"intMPMax":100,"tx":0,"ty":0,"afk":true,"showCloak":true,"showHelm":true},{"uoName":"hero 2","entType":"p","entID":109806,"strUsername":"Hero 2","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4120,"intMP":100,"intHPMax":4120,"intMPMax":100,"tx":0,"ty":0,"afk":true,"showCloak":true,"showHelm":true},{"uoName":"hero 3","entType":"p","entID":114893,"strUsername":"Hero 3","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":3630,"intMP":100,"intHPMax":3630,"intMPMax":100,"tx":0,"ty":0,"afk":true,"showCloak":true,"showHelm":true},{"uoName":"hero 4","entType":"p","entID":115673,"strUsername":"Hero 4","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4035,"intMP":100,"intHPMax":4035,"intMPMax":100,"tx":0,"ty":0,"afk":true,"showCloak":false,"showHelm":true},{"uoName":"hero 5","entType":"p","entID":116081,"strUsername":"Hero 5","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":2290,"intMP":100,"intHPMax":2290,"intMPMax":100,"tx":492,"ty":436,"afk":true,"showCloak":true,"showHelm":false},{"uoName":"hero 6","entType":"p","entID":116124,"strUsername":"Hero 6","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4100,"intMP":100,"intHPMax":4100,"intMPMax":100,"tx":0,"ty":0,"afk":true,"showCloak":true,"showHelm":true},{"uoName":"hero 7","entType":"p","entID":116486,"strUsername":"Hero 7","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4630,"intMP":100,"intHPMax":4630,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":true,"showHelm":true},{"uoName":"hero 8","entType":"p","entID":116488,"strUsername":"Hero 8","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":100,"intHP":4300,"intMP":100,"intHPMax":4300,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":false,"showHelm":true},{"uoName":"hero 9","entType":"p","entID":116500,"strUsername":"Hero 9","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":81,"intHP":500,"intMP":100,"intHPMax":500,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":true,"showHelm":true}],"monBranch":[]}}}
        JSON;

    #[Test]
    public function it_reads_where_a_real_move_landed(): void
    {
        $event = self::load(self::FOREST);

        static::assertSame(687_803, $event->areaId->value);
        static::assertSame(1, $event->roomId->value);
        static::assertSame('forest', $event->map->value);
        static::assertSame('GreenGuard/town-Forest-13Aug24.swf', $event->file->value);
        static::assertSame([1, 2, 32], array_map(static fn(MonsterId $id): int => $id->value, $event->monsters));
        static::assertEquals([new PlayerName('Hero')], $event->players);
    }

    #[Test]
    public function it_mentions_what_the_message_says_about_each_monster(): void
    {
        $boss = array_values(array_map(
            static fn(Mention $mention): object => $mention->value,
            array_filter(
                self::load(self::FOREST)->mentions(),
                static fn(Mention $mention): bool => $mention->about->value === 2,
            ),
        ));

        static::assertEquals(
            [
                new MonsterName('Boss Zardman'),
                new MonsterLevel(3),
                Race::None,
                new Gamefile('ZardmanBoss.swf'),
                new Linkage('ZardmanBoss'),
                new Health(970),
                new Mana(100),
            ],
            $boss,
        );
        static::assertCount(21, self::load(self::FOREST)->mentions());
    }

    #[Test]
    public function it_reads_a_real_map_without_monsters(): void
    {
        $event = self::load(self::BATTLEON);

        static::assertSame('battleon', $event->map->value);
        static::assertSame(2, $event->roomId->value);
        static::assertSame([], $event->monsters);
        static::assertCount(9, $event->players);
        static::assertSame('Hero 9', $event->players[8]->value);
        static::assertSame([], $event->mentions());
    }

    #[Test]
    #[TestWith(['None', Race::None])]
    #[TestWith(['Undead', Race::Undead])]
    #[TestWith(['Human', Race::Human])]
    #[TestWith(['Elemental', Race::Elemental])]
    #[TestWith(['Dragonkin', Race::Dragonkin])]
    #[TestWith(['Unknown', Race::Unknown])]
    public function it_translates_the_race(string $race, Race $expected): void
    {
        static::assertSame($expected, self::load(self::move('forest-1', $race))->mentions()[2]->value);
    }

    #[Test]
    #[DataProvider('malformed')]
    public function it_rejects_what_it_does_not_know(string $message, string $reason): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage($reason);

        self::load($message);
    }

    /** @return iterable<string, array{string, string}> */
    public static function malformed(): iterable
    {
        yield 'unknown race' => [self::move('forest-1', 'Robot'), "unknown race 'Robot'"];
        yield 'area without room' => [self::move('forest', 'None'), "area name 'forest' has no room number"];
        yield 'no map file' => [
            '{"b":{"o":{"cmd":"moveToArea","areaId":1,"areaName":"forest-1","strMapName":"forest","uoBranch":[],"monBranch":[]}}}',
            'map data is missing or invalid',
        ];
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            MovedToMapEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"loadShop"}}}')->unwrap())->isNone(),
        );
    }

    private static function load(string $message): MovedToMapEvent
    {
        return MovedToMapEvent::from(JsonMessage::from($message)->unwrap())->unwrap();
    }

    private static function move(string $areaName, string $race): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'moveToArea',
            'areaId' => 1,
            'areaName' => $areaName,
            'strMapName' => 'forest',
            'strMapFileName' => 'forest.swf',
            'mondef' => [[
                'MonID' => 1,
                'strMonName' => 'Hero Monster',
                'intLevel' => 1,
                'sRace' => $race,
                'strMonFileName' => 'Monster.swf',
                'strLinkage' => 'Monster',
            ]],
            'uoBranch' => [],
            'monBranch' => [],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
