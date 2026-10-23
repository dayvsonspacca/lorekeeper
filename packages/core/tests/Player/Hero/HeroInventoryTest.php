<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\HeroInventory;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

/** @mago-expect lint:too-many-methods */
final class HeroInventoryTest extends TestCase
{
    #[Test]
    public function it_starts_empty_with_the_slots_of_its_bag(): void
    {
        $inventory = new HeroInventory(new Quantity(131));

        static::assertSame(131, $inventory->slots->value);
        static::assertCount(0, $inventory->permanent);
        static::assertCount(0, $inventory->temporary);
    }

    #[Test]
    public function it_finds_an_item_whether_it_is_kept_or_temporary(): void
    {
        $healer = new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none());
        $ore = self::temporary(42_658, 'Metal Ore');
        $inventory = new HeroInventory(new Quantity(131))->add($healer)->add($ore);

        static::assertSame($healer, $inventory->find(new ItemId(15_651))->unwrap());
        static::assertSame($ore, $inventory->find(new ItemId(42_658))->unwrap());
        static::assertTrue($inventory->find(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_keeps_temporary_items_apart_from_the_bag(): void
    {
        $scales = self::resource(11_475, 'Dragon Scale');
        $ore = self::temporary(42_658, 'Metal Ore');
        $inventory = new HeroInventory(new Quantity(131))->add($scales)->add($ore);

        static::assertSame([11_475 => $scales], iterator_to_array($inventory->permanent));
        static::assertSame([42_658 => $ore], iterator_to_array($inventory->temporary));
    }

    #[Test]
    public function it_tells_how_many_of_an_item_it_holds(): void
    {
        $inventory = new HeroInventory(new Quantity(131))->add(
            self::resource(42_657, 'Crypto Token'),
            new Quantity(3),
        )->add(self::temporary(42_658, 'Metal Ore'));

        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
        static::assertSame(1, $inventory->quantity(new ItemId(42_658)));
        static::assertSame(0, $inventory->quantity(new ItemId(1)));
    }

    #[Test]
    public function it_holds_one_of_what_does_not_stack(): void
    {
        $inventory = new HeroInventory(new Quantity(131))->add(
            new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none()),
        );

        static::assertSame(1, $inventory->quantity(new ItemId(4_767)));
    }

    #[Test]
    public function it_rejects_more_than_one_of_what_does_not_stack(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HeroInventory(new Quantity(131))->add(
            new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none()),
            new Quantity(2),
        );
    }

    #[Test]
    public function it_rejects_more_than_a_stack_holds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HeroInventory(new Quantity(131))->add(self::resource(11_475, 'Dragon Scale'), new Quantity(1001));
    }

    #[Test]
    public function an_item_put_straight_into_the_bag_counts_as_one(): void
    {
        $inventory = new HeroInventory(new Quantity(131));
        $inventory->permanent->add(self::resource(11_475, 'Dragon Scale'));

        static::assertSame(1, $inventory->quantity(new ItemId(11_475)));
    }

    #[Test]
    public function it_forgets_how_many_it_held_once_the_item_is_gone(): void
    {
        $inventory = new HeroInventory(new Quantity(131))->add(
            self::resource(11_475, 'Dragon Scale'),
            new Quantity(50),
        );
        $inventory->permanent->remove(new ItemId(11_475));

        static::assertSame(0, $inventory->quantity(new ItemId(11_475)));
    }

    #[Test]
    public function each_different_kept_item_takes_one_slot_however_many_it_stacks(): void
    {
        $inventory = new HeroInventory(new Quantity(2))->add(
            self::resource(11_475, 'Dragon Scale'),
            new Quantity(50),
        )->add(self::temporary(42_658, 'Metal Ore'));

        static::assertSame(1, $inventory->free());
        static::assertFalse($inventory->full());

        $inventory->add(self::resource(42_657, 'Crypto Token'));

        static::assertSame(0, $inventory->free());
        static::assertTrue($inventory->full());
    }

    #[Test]
    public function it_has_no_free_slot_while_holding_more_than_its_bag_fits(): void
    {
        $inventory = new HeroInventory(new Quantity(1))->add(self::resource(
            11_475,
            'Dragon Scale',
        ))->add(self::resource(42_657, 'Crypto Token'));

        static::assertSame(0, $inventory->free());
        static::assertTrue($inventory->full());
    }

    #[Test]
    public function it_wears_one_kept_equipment_per_slot(): void
    {
        $helm = new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none());
        $healer = new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none());
        $inventory = new HeroInventory(new Quantity(131))
            ->add($helm)
            ->add($healer)
            ->equip($helm);

        static::assertSame($helm, $inventory->equipped(EquipmentSlot::Helm)->unwrap());
        static::assertTrue($inventory->equipped(EquipmentSlot::CharacterClass)->isNone());
    }

    #[Test]
    public function it_wears_nothing_once_the_equipment_is_gone(): void
    {
        $helm = new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none());
        $inventory = new HeroInventory(new Quantity(131))->add($helm)->equip($helm);
        $inventory->permanent->remove(new ItemId(4_767));

        static::assertTrue($inventory->equipped(EquipmentSlot::Helm)->isNone());
    }

    #[Test]
    public function it_rejects_equipping_what_it_does_not_keep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HeroInventory(new Quantity(131))->equip(
            new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none()),
        );
    }

    #[Test]
    public function it_rejects_keeping_an_item_it_already_holds_as_temporary(): void
    {
        $inventory = new HeroInventory(new Quantity(131))->add(self::temporary(42_658, 'Metal Ore'));

        $this->expectException(InvalidArgumentException::class);

        $inventory->add(self::resource(42_658, 'Metal Ore'));
    }

    #[Test]
    public function it_rejects_holding_as_temporary_an_item_it_already_keeps(): void
    {
        $inventory = new HeroInventory(new Quantity(131))->add(self::resource(42_658, 'Metal Ore'));

        $this->expectException(InvalidArgumentException::class);

        $inventory->add(self::temporary(42_658, 'Metal Ore'));
    }

    private static function resource(int $id, string $name): Resource
    {
        return new Resource(new ItemId($id), new ItemName($name), new ItemLevel(1), new Quantity(1000));
    }

    private static function temporary(int $id, string $name): Resource
    {
        $item = self::resource($id, $name);
        $item->tags->add(ItemTag::Temporary);

        return $item;
    }
}
