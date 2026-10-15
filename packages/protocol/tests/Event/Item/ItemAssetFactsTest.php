<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Item;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\Item\ItemAssetFacts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemAssetFactsTest extends TestCase
{
    #[Test]
    public function it_extracts_gamefile_and_linkage(): void
    {
        static::assertEquals(
            [
                new Fact(new ItemId(1), new Gamefile('PyromancerUpgrade.swf')),
                new Fact(new ItemId(1), new Linkage('PyromancerUpgrade')),
            ],
            ItemAssetFacts::of(new ItemId(1), 'PyromancerUpgrade.swf', 'PyromancerUpgrade'),
        );
    }

    #[Test]
    public function it_ignores_files_that_are_not_swf(): void
    {
        static::assertSame([], ItemAssetFacts::of(new ItemId(1), 'icb3', 'potion'));
    }
}
