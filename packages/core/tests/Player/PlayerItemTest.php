<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerItemTest extends TestCase
{
    #[Test]
    public function it_keeps_how_many_of_the_item_the_player_has(): void
    {
        $item = new PlayerItem(new ItemId(4767), new ItemName('Dragonbone Blade'), new Quantity(3));

        static::assertSame(4767, $item->id->value);
        static::assertSame('Dragonbone Blade', $item->name->value);
        static::assertSame(3, $item->quantity->value);
    }

    #[Test]
    public function it_has_one_unless_told_otherwise(): void
    {
        $item = new PlayerItem(new ItemId(4767), new ItemName('Dragonbone Blade'));

        static::assertSame(1, $item->quantity->value);
    }

    #[Test]
    public function it_starts_with_no_tags_and_takes_them_afterwards(): void
    {
        $blade = new PlayerItem(new ItemId(4767), new ItemName('Dragonbone Blade'));

        static::assertFalse($blade->tags->has(ItemTag::MemberOnly));

        $blade->tags->add(ItemTag::MemberOnly);

        static::assertTrue($blade->tags->has(ItemTag::MemberOnly));
    }
}
