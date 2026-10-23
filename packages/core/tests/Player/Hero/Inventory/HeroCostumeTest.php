<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class HeroCostumeTest extends TestCase
{
    #[Test]
    public function it_wears_a_costume_apart_from_what_is_equipped(): void
    {
        $iron = self::helm(4_767, 'Iron Helm');
        $dragon = self::helm(4_768, 'Dragon Helm');
        $inventory = self::inventory()->add($iron)->add($dragon);

        $inventory->equipment->equip($iron);
        $inventory->costume->wear($dragon);

        static::assertSame($iron, $inventory->equipment->equipped(EquipmentSlot::Helm)->unwrap());
        static::assertSame($dragon, $inventory->costume->worn(EquipmentSlot::Helm)->unwrap());
        static::assertTrue($inventory->costume->wears(new ItemId(4_768)));
        static::assertFalse($inventory->costume->wears(new ItemId(4_767)));
        static::assertTrue($inventory->costume->worn(EquipmentSlot::Cape)->isNone());
    }

    #[Test]
    public function the_same_item_can_be_equipped_and_worn(): void
    {
        $iron = self::helm(4_767, 'Iron Helm');
        $inventory = self::inventory()->add($iron);

        $inventory->equipment->equip($iron);
        $inventory->costume->wear($iron);

        static::assertTrue($inventory->equipment->equips(new ItemId(4_767)));
        static::assertTrue($inventory->costume->wears(new ItemId(4_767)));
    }

    #[Test]
    public function it_refuses_to_wear_what_the_hero_does_not_keep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::inventory()->costume->wear(self::helm(4_768, 'Dragon Helm'));
    }

    private static function inventory(): HeroInventory
    {
        return new HeroInventory(new Quantity(131), new Quantity(131));
    }

    private static function helm(int $id, string $name): Helm
    {
        return new Helm(new ItemId($id), new ItemName($name), new ItemLevel(1), Option\none());
    }
}
