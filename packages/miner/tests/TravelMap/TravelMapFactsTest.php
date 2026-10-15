<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\TravelMap;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Progression\LevelRange;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapTitle;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Core\World\Region\RegionName;
use Lorekeeper\Miner\Exception\TravelMapUnavailableException;
use Lorekeeper\Miner\TravelMap\TravelMapFacts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class TravelMapFactsTest extends TestCase
{
    private const string TRAVEL_MAP = '[{"regionID":1,"regionName":"Greenguard","description":"g1","icon":"battleon","strLink":"greenguardr2","maps":[{"regionID":1,"mapID":1,"mapName":"battleon","bUpgrade":false,"minLevel":1,"maxLevel":1,"qsValue":-1,"questMin":0,"questMax":0,"type":0,"description":"Battleon\'s new home!","strExtra":"","icon":"battleon","mapTitle":"Battleon"},{"regionID":1,"mapID":47,"mapName":"shallow","bUpgrade":true,"minLevel":8,"maxLevel":8,"qsValue":-1,"questMin":0,"questMax":0,"type":3,"description":"The shallow bank of the river holds an elemental secret.","strExtra":"","icon":"","mapTitle":"Shallow"}]},{"regionID":19,"regionName":"Town of Battleon","description":"d19","icon":"battleon","strLink":"battleonr3","maps":[{"regionID":19,"mapID":1,"mapName":"battleon","bUpgrade":false,"minLevel":0,"maxLevel":0,"qsValue":-1,"questMin":0,"questMax":0,"type":0,"description":"The main town...","strExtra":"","icon":"battleon","mapTitle":"Battleon"}]},{"regionID":72,"regionName":"Empty","description":"","icon":"","strLink":""}]';

    #[Test]
    public function it_extracts_region_and_map_facts(): void
    {
        $greenguard = new RegionId(1);
        $town = new RegionId(19);
        $battleon = new MapName('battleon');
        $shallow = new MapName('shallow');

        static::assertEquals(
            [
                new Fact($greenguard, new RegionName('Greenguard')),
                new Fact(
                    $greenguard,
                    new RegionMap(
                        $battleon,
                        new MapTitle('Battleon'),
                        Option\some(new MapDescription("Battleon's new home!")),
                        new LevelRange(1, 1),
                    ),
                ),
                new Fact(
                    $greenguard,
                    new RegionMap(
                        $shallow,
                        new MapTitle('Shallow'),
                        Option\some(new MapDescription('The shallow bank of the river holds an elemental secret.')),
                        new LevelRange(8, 8),
                    ),
                ),
                new Fact($town, new RegionName('Town of Battleon')),
                new Fact(
                    $town,
                    new RegionMap(
                        $battleon,
                        new MapTitle('Battleon'),
                        Option\some(new MapDescription('The main town...')),
                        new LevelRange(0, 0),
                    ),
                ),
                new Fact(new RegionId(72), new RegionName('Empty')),
                new Fact($battleon, new MapId(1)),
                new Fact($shallow, new MapId(47)),
                new Fact($shallow, new MemberOnly()),
            ],
            TravelMapFacts::fromJson(self::TRAVEL_MAP),
        );
    }

    #[Test]
    public function it_handles_blank_descriptions(): void
    {
        $facts = TravelMapFacts::fromJson(
            '[{"regionID":1,"regionName":"Greenguard","maps":[{"mapID":1,"mapName":"battleon","mapTitle":"Battleon","description":" ","bUpgrade":false,"minLevel":1,"maxLevel":1}]}]',
        );

        static::assertEquals(
            new RegionMap(new MapName('battleon'), new MapTitle('Battleon'), Option\none(), new LevelRange(1, 1)),
            $facts[1]->value,
        );
    }

    #[Test]
    public function it_fails_on_unexpected_responses(): void
    {
        $this->expectException(TravelMapUnavailableException::class);

        TravelMapFacts::fromJson('<html>maintenance</html>');
    }
}
