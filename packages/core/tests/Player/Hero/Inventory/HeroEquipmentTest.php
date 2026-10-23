<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class HeroEquipmentTest extends TestCase
{
    #[Test]
    public function it_wears_one_kept_equipment_per_slot(): void
    {
        $helm = self::helm();
        $healer = new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none());
        $inventory = new HeroInventory(new Quantity(131), new Quantity(131))->add($helm)->add($healer);
        $equipment = $inventory->equipment->equip($helm);

        static::assertSame($helm, $equipment->equipped(EquipmentSlot::Helm)->unwrap());
        static::assertTrue($equipment->equipped(EquipmentSlot::CharacterClass)->isNone());
    }

    #[Test]
    public function it_wears_nothing_once_the_equipment_is_gone(): void
    {
        $helm = self::helm();
        $inventory = new HeroInventory(new Quantity(131), new Quantity(131))->add($helm);
        $equipment = $inventory->equipment->equip($helm);
        $inventory->take(new ItemId(4_767), new Quantity(1));

        static::assertTrue($equipment->equipped(EquipmentSlot::Helm)->isNone());
    }

    #[Test]
    public function it_tells_whether_it_wears_an_item(): void
    {
        $helm = self::helm();
        $healer = new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none());
        $scales = new Resource(new ItemId(11_475), new ItemName('Dragon Scale'), new ItemLevel(1), new Quantity(1000));
        $inventory = new HeroInventory(new Quantity(131), new Quantity(131))->add($helm)->add($healer)->add($scales);
        $inventory->equipment->equip($helm);

        static::assertTrue($inventory->equipment->wears(new ItemId(4_767)));
        static::assertFalse($inventory->equipment->wears(new ItemId(15_651)));
        static::assertFalse($inventory->equipment->wears(new ItemId(11_475)));
        static::assertFalse($inventory->equipment->wears(new ItemId(1)));
    }

    #[Test]
    public function it_rejects_equipping_what_the_hero_does_not_keep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HeroInventory(new Quantity(131), new Quantity(131))->equipment->equip(self::helm());
    }

    private static function helm(): Helm
    {
        return new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none());
    }
}
