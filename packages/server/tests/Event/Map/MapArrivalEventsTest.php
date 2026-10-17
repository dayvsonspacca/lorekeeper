<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Lorekeeper\Server\Event\Map\MonstersPlacedEvent;
use Lorekeeper\Server\Event\Map\PlayersPresentEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapArrivalEventsTest extends TestCase
{
    private const string ICESTORM = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":732692,"areaName":"icestormarena-48213","intType":"1","sExtra":"NoAfk","strMapName":"icestormarena","strMapFileName":"Icewing/town-icestormarena-21Jul23.swf","monmap":[{"MonMapID":1,"MonID":3984,"strFrame":"r4","bRed":false,"intRSS":-1},{"MonMapID":2,"MonID":3984,"strFrame":"r4","bRed":false,"intRSS":-1},{"MonMapID":5,"MonID":3985,"strFrame":"r5","bRed":false,"intRSS":-1}],"mondef":[{"sRace":"Undead","MonID":3984,"intLevel":5,"strLinkage":"UndeadMage1","strMonName":"Skeletal Ice Mage","strMonFileName":"UndeadMage1.swf","strBehave":"walk"}],"uoBranch":[{"uoName":"lorekeeper03","entType":"p","entID":122507,"strUsername":"lorekeeper03","strFrame":"Enter","strPad":"Spawn","intState":1,"intLevel":1,"intHP":390,"intMP":100,"intHPMax":390,"intMPMax":100,"tx":0,"ty":0,"afk":false,"showCloak":false,"showHelm":false}],"monBranch":[{"MonMapID":1,"MonID":3984,"intState":1,"intHP":730,"intMP":100,"intHPMax":730,"intMPMax":100,"bRed":false,"iLvl":5,"wDPS":5}]}}}
        JSON;

    #[Test]
    public function it_reads_which_monster_stands_in_which_cell(): void
    {
        $placed = MonstersPlacedEvent::from(JsonMessage::from(self::ICESTORM)->unwrap())->unwrap();

        static::assertSame(
            [[1, 3984, 'r4', 5], [2, 3984, 'r4', 5], [5, 3985, 'r5', null]],
            array_map(static fn(MonsterPlacement $placement): array => [
                $placement->id->value,
                $placement->monster->value,
                $placement->cell->value,
                $placement->level?->value,
            ], $placed->placements),
        );
    }

    #[Test]
    public function it_reads_the_players_in_the_map_with_their_level(): void
    {
        $present = PlayersPresentEvent::from(JsonMessage::from(self::ICESTORM)->unwrap())->unwrap();

        static::assertCount(1, $present->players);
        static::assertSame('lorekeeper03', $present->players[0]->name->value);
        static::assertSame(1, $present->players[0]->level?->value);
    }

    #[Test]
    public function a_map_without_monsters_places_none(): void
    {
        $placed = MonstersPlacedEvent::from(
            JsonMessage::from('{"b":{"o":{"cmd":"moveToArea","uoBranch":[{"strUsername":"Hero"}]}}}')->unwrap(),
        )->unwrap();
        $present = PlayersPresentEvent::from(
            JsonMessage::from('{"b":{"o":{"cmd":"moveToArea","uoBranch":[{"strUsername":"Hero"}]}}}')->unwrap(),
        )->unwrap();

        static::assertSame([], $placed->placements);
        static::assertNull($present->players[0]->level);
    }

    #[Test]
    public function they_ignore_other_messages_and_reject_what_they_cannot_read(): void
    {
        static::assertTrue(
            MonstersPlacedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"ct"}}}')->unwrap())->isNone(),
        );
        static::assertTrue(
            PlayersPresentEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"ct"}}}')->unwrap())->isNone(),
        );

        $this->expectException(MalformedEventException::class);
        PlayersPresentEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"moveToArea"}}}')->unwrap());
    }
}
