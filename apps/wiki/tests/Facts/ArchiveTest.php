<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Facts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Wiki\Facts\Archive;
use Lorekeeper\Apps\Wiki\Facts\Fact;
use Lorekeeper\Apps\Wiki\Facts\Reading\MessageFacts;
use Lorekeeper\Apps\Wiki\Models\Armor;
use Lorekeeper\Apps\Wiki\Models\Item;
use Lorekeeper\Apps\Wiki\Models\ItemTag;
use Lorekeeper\Apps\Wiki\Tests\GameMessages;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemTag as Tag;
use Lorekeeper\Core\World\Shop\ShopId;

final class ArchiveTest extends TestCase
{
    use GameMessages;
    use RefreshDatabase;

    public function testAnArmorIsKeptAsAnItemAndAnArmor(): void
    {
        self::keep(self::shop([self::gladiator()]));

        $item = Item::query()->where('aqw_id', 16_416)->sole();
        static::assertSame('Gladiator of Circumstance', $item->name);
        static::assertSame($item->id, $item->armor?->item_id);
        static::assertSame(
            $item->id,
            Armor::query()
                ->sole()
                ->item
                ->id,
        );
    }

    public function testAnItemOfAKindNotKeptYetIsStillKept(): void
    {
        self::keep(self::shop([self::pendant()]));

        $item = Item::query()->where('aqw_id', 783)->sole();
        static::assertSame('Ruby Pendant', $item->name);
        static::assertNull($item->armor);
    }

    public function testTagsAreKeptOnce(): void
    {
        $tagged = [...self::gladiator(), 'bUpg' => true, 'bTemp' => true];
        self::keep(self::shop([$tagged]));
        self::keep(['cmd' => 'dropItem', 'items' => ['16416' => $tagged]]);

        $tags = Item::query()
            ->where('aqw_id', 16_416)
            ->sole()
            ->tags->pluck('tag')->all();
        static::assertEqualsCanonicalizing([Tag::MemberOnly, Tag::Temporary], $tags);
        static::assertSame(2, ItemTag::query()->count());
        static::assertSame(1, Armor::query()->count());
    }

    public function testAnItemSeenAgainKeepsOneRowAndItsNewestName(): void
    {
        self::keep(self::shop([self::gladiator()]));
        self::keep(['cmd' => 'loadInventoryBig', 'items' => [[...self::gladiator(), 'sName' => 'Gladiator Renamed']]]);

        static::assertSame('Gladiator Renamed', Item::query()->sole()->name);
    }

    public function testAnItemKnownOnlyByItsIdIsKeptWithoutAName(): void
    {
        Archive::record([new Fact(new ItemId(9392), new ShopId(1))]);

        static::assertNull(
            Item::query()
                ->where('aqw_id', 9392)
                ->sole()
                ->name,
        );
    }

    public function testAKnownNameStaysWhenAnItemIsOnlyMentioned(): void
    {
        self::keep(self::shop([self::gladiator()]));
        Archive::record([new Fact(new ItemId(16_416), new ShopId(1))]);

        static::assertSame('Gladiator of Circumstance', Item::query()->sole()->name);
    }

    public function testFactsAboutOtherSubjectsKeepNothing(): void
    {
        Archive::record([new Fact(new ShopId(31), new ItemId(1))]);
        Archive::record([]);
        self::keep(['cmd' => 'moveToArea', 'strMapName' => 'battleon']);

        static::assertSame(0, Item::query()->count());
    }

    /** @param array<string, mixed> $data */
    private static function keep(array $data): void
    {
        Archive::record(MessageFacts::of(self::message($data)));
    }
}
