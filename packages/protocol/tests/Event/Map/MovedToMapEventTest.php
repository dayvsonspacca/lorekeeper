<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MovedToMapEventTest extends TestCase
{
    private const string FOREST = '{"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":174547,"areaName":"forest-1","intType":"1","sExtra":"","strMapName":"forest","strMapFileName":"GreenGuard/town-Forest-13Aug24.swf","monmap":[{"MonMapID":1,"MonID":32,"strFrame":"Forest1","bRed":false,"intRSS":-1},{"MonMapID":2,"MonID":32,"strFrame":"Forest1","bRed":false,"intRSS":-1},{"MonMapID":3,"MonID":32,"strFrame":"Forest2","bRed":false,"intRSS":-1}],"mondef":[{"sRace":"None","MonID":1,"intLevel":3,"strLinkage":"Zardman","strMonName":"Zardman Grunt","strMonFileName":"Zardman.swf","strBehave":"walk"},{"sRace":"None","MonID":2,"intLevel":3,"strLinkage":"ZardmanBoss","strMonName":"Boss Zardman","strMonFileName":"ZardmanBoss.swf","strBehave":"walk"},{"sRace":"None","MonID":32,"intLevel":3,"strLinkage":"Zardman2","strMonName":"Zardman Hunter","strMonFileName":"Zardman2.swf","strBehave":"walk"}],"uoBranch":[],"monBranch":[{"MonMapID":1,"MonID":32,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":2,"MonID":32,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":4,"MonID":1,"intState":1,"intHP":610,"intMP":100,"intHPMax":610,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":1},{"MonMapID":8,"MonID":2,"intState":1,"intHP":970,"intMP":100,"intHPMax":970,"intMPMax":100,"bRed":false,"iLvl":3,"wDPS":2}]}}}';

    private const string BATTLEON = '{"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":172760,"areaName":"battleon-2","intType":"2","sExtra":"","strMapName":"battleon","strMapFileName":"Battleon/town-Battleon-9Oct26.swf","uoBranch":[],"monBranch":[]}}}';

    #[Test]
    public function it_carries_session_information(): void
    {
        $event = MovedToMapEvent::from(JsonMessage::from(self::FOREST)->unwrap())->unwrap();

        static::assertSame(174_547, $event->areaId->value);
        static::assertSame(1, $event->roomId->value);
        static::assertSame('forest', $event->mapName->value);
    }

    #[Test]
    public function it_extracts_map_and_monster_facts(): void
    {
        $event = MovedToMapEvent::from(JsonMessage::from(self::FOREST)->unwrap())->unwrap();

        $forest = new MapName('forest');
        $grunt = new MonsterId(1);
        $boss = new MonsterId(2);
        $hunter = new MonsterId(32);

        static::assertEquals(
            [
                new Fact($forest, new Gamefile('GreenGuard/town-Forest-13Aug24.swf')),
                new Fact($forest, new MapMonster($grunt)),
                new Fact($grunt, new MonsterName('Zardman Grunt')),
                new Fact($grunt, new MonsterLevel(3)),
                new Fact($grunt, new Gamefile('Zardman.swf')),
                new Fact($grunt, new Linkage('Zardman')),
                new Fact($grunt, Race::None),
                new Fact($forest, new MapMonster($boss)),
                new Fact($boss, new MonsterName('Boss Zardman')),
                new Fact($boss, new MonsterLevel(3)),
                new Fact($boss, new Gamefile('ZardmanBoss.swf')),
                new Fact($boss, new Linkage('ZardmanBoss')),
                new Fact($boss, Race::None),
                new Fact($forest, new MapMonster($hunter)),
                new Fact($hunter, new MonsterName('Zardman Hunter')),
                new Fact($hunter, new MonsterLevel(3)),
                new Fact($hunter, new Gamefile('Zardman2.swf')),
                new Fact($hunter, new Linkage('Zardman2')),
                new Fact($hunter, Race::None),
                new Fact($hunter, new Health(610)),
                new Fact($hunter, new Mana(100)),
                new Fact($grunt, new Health(610)),
                new Fact($grunt, new Mana(100)),
                new Fact($boss, new Health(970)),
                new Fact($boss, new Mana(100)),
            ],
            $event->facts,
        );
    }

    #[Test]
    public function it_handles_maps_without_monsters(): void
    {
        $event = MovedToMapEvent::from(JsonMessage::from(self::BATTLEON)->unwrap())->unwrap();

        static::assertSame(2, $event->roomId->value);
        static::assertEquals(
            [new Fact(new MapName('battleon'), new Gamefile('Battleon/town-Battleon-9Oct26.swf'))],
            $event->facts,
        );

        $withFalseLists = MovedToMapEvent::from(self::message(['mondef' => false, 'monBranch' => false]))->unwrap();
        static::assertCount(1, $withFalseLists->facts);
    }

    #[Test]
    public function it_records_no_map_facts_for_houses(): void
    {
        $event = MovedToMapEvent::from(self::message([
            'areaId' => 541_250,
            'areaName' => 'house-24280076',
            'strMapName' => 'house',
            'strMapFileName' => 'houses/house-FantasyTownhouse.swf',
        ]))->unwrap();

        static::assertSame('house', $event->mapName->value);
        static::assertSame(24_280_076, $event->roomId->value);
        static::assertSame([], $event->facts);
    }

    #[Test]
    public function it_skips_unknown_races(): void
    {
        $event = MovedToMapEvent::from(self::message(['mondef' => [[
            'MonID' => 7,
            'strMonName' => 'Frogzard',
            'intLevel' => 1,
            'sRace' => 'Amphibian',
            'strMonFileName' => 'Frogzard.swf',
            'strLinkage' => 'Frogzard',
        ]]]))->unwrap();

        foreach ($event->facts as $fact) {
            static::assertNotInstanceOf(Race::class, $fact->value);
        }
    }

    /** @return iterable<string, array{Message}> */
    public static function otherMessages(): iterable
    {
        yield 'other command' => [JsonMessage::from('{"b":{"o":{"cmd":"loadInventoryBig"}}}')->unwrap()];
        yield 'delimited message' => [DelimitedMessage::from('%xt%server%-1%You joined "battleon-2"%')->unwrap()];
    }

    #[Test]
    #[DataProvider('otherMessages')]
    public function it_ignores_other_messages(Message $message): void
    {
        static::assertTrue(MovedToMapEvent::from($message)->isNone());
    }

    #[Test]
    public function it_rejects_missing_map_file(): void
    {
        $this->expectException(MalformedEventException::class);

        MovedToMapEvent::from(self::message(['strMapFileName' => null]));
    }

    #[Test]
    public function it_rejects_area_name_without_room(): void
    {
        $this->expectException(MalformedEventException::class);

        MovedToMapEvent::from(self::message(['areaName' => 'forest']));
    }

    /** @param array<string, mixed> $overrides */
    private static function message(array $overrides): JsonMessage
    {
        return new JsonMessage([
            ...[
                'cmd' => 'moveToArea',
                'areaId' => 1,
                'areaName' => 'forest-1',
                'strMapName' => 'forest',
                'strMapFileName' => 'GreenGuard/town-Forest-13Aug24.swf',
            ],
            ...$overrides,
        ], '');
    }
}
