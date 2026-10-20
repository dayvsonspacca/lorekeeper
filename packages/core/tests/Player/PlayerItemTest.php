<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class PlayerItemTest extends TestCase
{
    #[Test]
    public function it_keeps_how_many_of_the_item_the_player_has(): void
    {
        $scales = new Resource(new ItemId(11_475), new ItemName('Dragon Scale'), new ItemLevel(1));
        $held = new PlayerItem($scales, new Quantity(5));

        static::assertSame($scales, $held->item);
        static::assertSame(5, $held->quantity->value);
    }

    #[Test]
    public function it_has_one_unless_told_otherwise(): void
    {
        $held = new PlayerItem(
            new Weapon(
                new ItemId(4767),
                new ItemName('Unidentified 20'),
                new ItemLevel(1),
                WeaponType::Sword,
                new WeaponDamage(new DamageVariance(10)),
                Option\none(),
            ),
        );

        static::assertSame(1, $held->quantity->value);
    }

    #[Test]
    public function its_item_starts_with_no_tags_and_takes_them_afterwards(): void
    {
        $voucher = new Resource(new ItemId(4861), new ItemName('Voucher of Nulgath'), new ItemLevel(1));

        static::assertFalse($voucher->tags->has(ItemTag::MemberOnly));

        $voucher->tags->add(ItemTag::MemberOnly);

        static::assertTrue(new PlayerItem($voucher)->item->tags->has(ItemTag::MemberOnly));
    }
}
