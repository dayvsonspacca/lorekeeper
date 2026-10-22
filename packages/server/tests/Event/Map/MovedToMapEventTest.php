<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
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

    private const string ICESTORM = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":732692,"areaName":"icestormarena-48213","intType":"1","sExtra":"NoAfk","strMapName":"icestormarena","strMapFileName":"Icewing/town-icestormarena-21Jul23.swf","monmap":[{"MonMapID":1,"MonID":3984,"strFrame":"r4","bRed":false,"intRSS":-1},{"MonMapID":2,"MonID":3984,"strFrame":"r4","bRed":false,"intRSS":-1},{"MonMapID":5,"MonID":3985,"strFrame":"r5","bRed":false,"intRSS":-1}],"mondef":[{"sRace":"Undead","MonID":3984,"intLevel":5,"strLinkage":"UndeadMage1","strMonName":"Skeletal Ice Mage","strMonFileName":"UndeadMage1.swf","strBehave":"walk"}],"uoBranch":[{"uoName":"Hero10","entType":"p","entID":122507,"strUsername":"Hero10","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":1,"intHP":390,"intMP":100,"intHPMax":390,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":false,"showHelm":false}],"monBranch":[{"MonMapID":1,"MonID":3984,"intState":1,"intHP":730,"intMP":100,"intHPMax":730,"intMPMax":100,"bRed":false,"iLvl":5,"wDPS":5}]}}}
        JSON;

    #[Test]
    public function it_reads_where_a_real_move_landed(): void
    {
        $forest = MovedToMapEvent::from(self::message(self::FOREST))->unwrap();
        $battleon = MovedToMapEvent::from(self::message(self::BATTLEON))->unwrap();

        static::assertSame(687_803, $forest->areaId->value);
        static::assertSame(1, $forest->roomId->value);
        static::assertSame('forest', $forest->map->name->value);
        static::assertSame('GreenGuard/town-Forest-13Aug24.swf', $forest->map->file->value);
        static::assertSame('forest (room 1)', $forest->summary());
        static::assertSame(2, $battleon->roomId->value);
    }

    #[Test]
    public function it_lays_out_each_monster_of_the_map_in_its_cell(): void
    {
        $forest = MovedToMapEvent::from(self::message(self::FOREST))->unwrap()->map;

        static::assertSame(
            [
                [1, 'Zardman Hunter', 'Forest1'],
                [2, 'Zardman Hunter', 'Forest1'],
                [3, 'Zardman Hunter', 'Forest2'],
                [4, 'Zardman Grunt',  'Forest2'],
                [5, 'Zardman Grunt',  'Forest3'],
                [6, 'Zardman Grunt',  'Forest3'],
                [7, 'Zardman Grunt',  'Forest3'],
                [8, 'Boss Zardman',   'End'],
            ],
            array_map(static fn(MapMonster $monster): array => [
                $monster->id->value,
                $monster->monster->name->value,
                $monster->cell->value,
            ], $forest->monsters),
        );

        $grunt = $forest->monsters[3];
        static::assertSame(3, $grunt->monster->level->value);
        static::assertSame(MonsterRace::None, $grunt->monster->race);
        static::assertSame('Zardman.swf', $grunt->monster->appearance->file->value);
        static::assertSame('Zardman', $grunt->monster->appearance->linkage->value);
        static::assertSame(CombatState::Idle, $grunt->state);
        static::assertSame([610, 610, 100, 100], [
            $grunt->vitals->health->current->value,
            $grunt->vitals->health->maximum->value,
            $grunt->vitals->mana->current->value,
            $grunt->vitals->mana->maximum->value,
        ]);
        static::assertSame(970, $forest->monsters[7]->vitals->health->maximum->value);
        static::assertSame(
            [],
            MovedToMapEvent::from(self::message(self::BATTLEON))
                ->unwrap()
                ->map
                ->monsters,
        );
    }

    #[Test]
    public function it_leaves_out_the_spawns_the_message_does_not_fully_describe(): void
    {
        $arena = MovedToMapEvent::from(self::message(self::ICESTORM))->unwrap()->map;

        static::assertSame(
            [1],
            array_map(static fn(MapMonster $monster): int => $monster->id->value, $arena->monsters),
        );
        static::assertSame('Skeletal Ice Mage', $arena->monsters[0]->monster->name->value);
        static::assertSame('r4', $arena->monsters[0]->cell->value);
    }

    #[Test]
    #[TestWith(['None', MonsterRace::None])]
    #[TestWith(['Undead', MonsterRace::Undead])]
    #[TestWith(['Human', MonsterRace::Human])]
    #[TestWith(['Elemental', MonsterRace::Elemental])]
    #[TestWith(['Dragonkin', MonsterRace::Dragonkin])]
    #[TestWith(['Chaos', MonsterRace::Chaos])]
    #[TestWith(['Unknown', MonsterRace::Unknown])]
    public function it_translates_the_race(string $race, MonsterRace $expected): void
    {
        static::assertSame(
            $expected,
            MovedToMapEvent::from(self::message(self::move($race)))
                ->unwrap()
                ->map
                ->monsters[0]->monster->race,
        );
    }

    #[Test]
    public function it_sees_each_player_in_the_map(): void
    {
        $seen = MovedToMapEvent::from(self::message(self::BATTLEON))->unwrap();
        $last = $seen->players[8]->player;

        static::assertCount(9, $seen->players);
        static::assertSame(116_500, $seen->players[8]->socketId->value);
        static::assertSame('Hero 9', $last->name->value);
        static::assertSame(81, $last->level->value);
        static::assertSame('Enter', $last->spot->cell->value);
        static::assertSame(MapPad::Spawn, $last->spot->pad);
        static::assertSame(CombatState::Idle, $last->state);
        static::assertSame([500, 500, 100, 100], [
            $last->vitals->health->current->value,
            $last->vitals->health->maximum->value,
            $last->vitals->mana->current->value,
            $last->vitals->mana->maximum->value,
        ]);
        static::assertFalse($last->away);
        static::assertTrue($seen->players[0]->player->away);
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        static::assertTrue(MovedToMapEvent::from(self::message('{"b":{"o":{"cmd":"loadShop"}}}'))->isNone());
    }

    #[Test]
    #[TestWith([
        '{"b":{"o":{"cmd":"moveToArea","areaId":1,"areaName":"forest","strMapName":"forest","strMapFileName":"f.swf"}}}',
        "area name 'forest' has no room number",
    ])]
    #[TestWith([
        '{"b":{"o":{"cmd":"moveToArea","areaId":1,"areaName":"forest-1","strMapName":"forest"}}}',
        'map data is missing or invalid',
    ])]
    public function a_move_it_cannot_read_is_rejected(string $message, string $reason): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage($reason);

        MovedToMapEvent::from(self::message($message));
    }

    #[Test]
    public function an_unknown_race_is_rejected(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown race 'Robot'");

        MovedToMapEvent::from(self::message(self::move('Robot')));
    }

    private static function message(string $json): Message
    {
        return JsonMessage::from($json)->unwrap();
    }

    private static function move(string $race): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'moveToArea',
            'areaId' => 1,
            'areaName' => 'forest-1',
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
            'monmap' => [['MonMapID' => 1, 'MonID' => 1, 'strFrame' => 'r1']],
            'monBranch' => [[
                'MonMapID' => 1,
                'intState' => 1,
                'intHP' => 100,
                'intHPMax' => 100,
                'intMP' => 100,
                'intMPMax' => 100,
            ]],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
