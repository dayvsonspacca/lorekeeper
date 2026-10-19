<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Player\PlayerItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class PlayerItemTest extends TestCase
{
    #[Test]
    public function it_keeps_how_many_of_the_item_the_player_has(): void
    {
        $item = new PlayerItem(new ItemId(4767), new ItemName('Dragonbone Blade'), 3);

        static::assertSame(4767, $item->id->value);
        static::assertSame('Dragonbone Blade', $item->name->value);
        static::assertSame(3, $item->quantity);
    }

    #[Test]
    public function it_rejects_having_none_of_it(): void
    {
        $this->expectException(AssertException::class);

        new PlayerItem(new ItemId(4767), new ItemName('Dragonbone Blade'), 0);
    }
}
