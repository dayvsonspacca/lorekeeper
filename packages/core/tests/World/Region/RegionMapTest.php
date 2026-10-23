<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Region;

use Lorekeeper\Core\Game\Level\LevelRange;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapTitle;
use Lorekeeper\Core\World\Region\RegionMap;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class RegionMapTest extends TestCase
{
    #[Test]
    public function it_holds_map_title_description_and_levels(): void
    {
        $map = new RegionMap(
            new MapName('battleon'),
            new MapTitle('Battleon'),
            Option\some(new MapDescription('The town of heroes.')),
            new LevelRange(1, 10),
        );

        static::assertSame('battleon', $map->mapName->value);
        static::assertSame('Battleon', $map->title->value);
        static::assertSame('The town of heroes.', $map->description->unwrap()->value);
        static::assertSame(1, $map->levels->minimum);
        static::assertSame(10, $map->levels->maximum);
    }

    #[Test]
    public function it_allows_a_map_without_description(): void
    {
        $map = new RegionMap(new MapName('battleon'), new MapTitle('Battleon'), Option\none(), new LevelRange(1, 10));

        static::assertTrue($map->description->isNone());
    }
}
