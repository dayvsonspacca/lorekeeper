<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Api\TravelMap;

use Lorekeeper\Client\Api\TravelMap\TravelMap;
use Lorekeeper\Client\Api\TravelMap\TravelMapClient;
use Lorekeeper\Client\Api\TravelMap\TravelMapUnavailableException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TravelMapTest extends TestCase
{
    private const string TRAVEL_MAP = <<<'JSON'
        [{"maps":[{"regionID":1,"mapID":1,"mapName":"battleon","bUpgrade":false,"minLevel":1,"maxLevel":1,"qsValue":-1,"questMin":0,"questMax":0,"type":0,"description":"Battleon's new home!","strExtra":"","icon":"battleon","mapTitle":"Battleon"},{"regionID":1,"mapID":47,"mapName":"shallow","bUpgrade":true,"minLevel":8,"maxLevel":8,"qsValue":-1,"questMin":0,"questMax":0,"type":3,"description":"The shallow bank of the river holds an elemental secret.","strExtra":"","icon":"","mapTitle":"Shallow"}],"regionID":1,"regionName":"Greenguard","description":"g1","icon":"battleon","strLink":"greenguardr2"},{"maps":[{"regionID":35,"mapID":1,"mapName":"battleon","bUpgrade":false,"minLevel":45,"maxLevel":60,"qsValue":193,"questMin":0,"questMax":32,"type":2,"description":"Battle through a high-level version of Artix's Ebil Dread Space zone!","strExtra":"newMap:True","icon":"artix","mapTitle":"Ebil Dread Space"}],"regionID":35,"regionName":"Return of the Living Devs","description":"Summer 2015 Event","icon":"battleon","strLink":"summer2015r7r1"}]
        JSON;

    #[Test]
    public function it_reads_how_each_region_presents_its_maps(): void
    {
        [$greenguard, $devs] = TravelMap::fromJson(self::TRAVEL_MAP)->regions;

        static::assertSame(1, $greenguard->id->value);
        static::assertSame('Greenguard', $greenguard->name->value);

        [$battleon, $shallow] = $greenguard->maps;
        static::assertSame(1, $battleon->id->value);
        static::assertSame('battleon', $battleon->map->mapName->value);
        static::assertSame('Battleon', $battleon->map->title->value);
        static::assertSame("Battleon's new home!", $battleon->map->description->unwrap()->value);
        static::assertSame(1, $battleon->map->levels->minimum);
        static::assertFalse($battleon->memberOnly);
        static::assertTrue($shallow->memberOnly);

        $ebil = $devs->maps[0];
        static::assertSame(1, $ebil->id->value);
        static::assertSame('battleon', $ebil->map->mapName->value);
        static::assertSame('Ebil Dread Space', $ebil->map->title->value);
        static::assertSame(45, $ebil->map->levels->minimum);
        static::assertSame(60, $ebil->map->levels->maximum);
    }

    #[Test]
    public function it_reads_regions_without_maps_and_maps_without_description(): void
    {
        [$region] = TravelMap::fromJson(
            '[{"regionID":9,"regionName":"Empty"},{"regionID":10,"regionName":"Quiet","maps":[{"mapID":2,"mapName":"quiet","mapTitle":"Quiet","description":" ","bUpgrade":false,"minLevel":0,"maxLevel":0}]}]',
        )->regions;

        static::assertSame([], $region->maps);
        static::assertTrue(
            TravelMap::fromJson(
                '[{"regionID":10,"regionName":"Quiet","maps":[{"mapID":2,"mapName":"quiet","mapTitle":"Quiet","description":" ","bUpgrade":false,"minLevel":0,"maxLevel":0}]}]',
            )->regions[0]->maps[0]
                ->map
                ->description
                ->isNone(),
        );
    }

    #[Test]
    public function it_rejects_an_unexpected_response(): void
    {
        $this->expectException(TravelMapUnavailableException::class);

        TravelMap::fromJson('{"error":true}');
    }

    #[Test]
    public function it_fetches_the_travel_map(): void
    {
        $response = tempnam(sys_get_temp_dir(), prefix: 'travelmap');
        file_put_contents($response, data: self::TRAVEL_MAP);

        try {
            $travelMap = new TravelMapClient("file://{$response}")->fetch();
        } finally {
            unlink($response);
        }

        static::assertCount(2, $travelMap->regions);
    }

    #[Test]
    public function it_fails_when_the_travel_map_cannot_be_reached(): void
    {
        $this->expectException(TravelMapUnavailableException::class);
        $this->expectExceptionMessage('Could not reach the travel map API');

        new TravelMapClient('http://127.0.0.1:1/')->fetch();
    }
}
