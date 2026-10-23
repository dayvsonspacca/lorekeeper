<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

/** @mago-expect lint:too-many-methods */
final class HeroBankTest extends TestCase
{
    #[Test]
    public function it_keeps_items_apart_from_the_bag(): void
    {
        $inventory = self::inventory();
        $inventory->bank->add(self::blood(), new Quantity(20));

        static::assertSame(20, $inventory->bank->quantity(new ItemId(5586)));
        static::assertSame(0, $inventory->quantity(new ItemId(5586)));
        static::assertTrue($inventory->bank->find(new ItemId(1))->isNone());
        static::assertSame(0, $inventory->bank->quantity(new ItemId(1)));
        static::assertSame(0, $inventory->bank->free());
    }

    #[Test]
    public function a_banked_item_that_does_not_stack_counts_as_one(): void
    {
        $inventory = self::inventory();
        $inventory->bank->add(self::helm());

        static::assertSame(1, $inventory->bank->quantity(new ItemId(4_767)));
    }

    #[Test]
    public function it_refuses_temporary_items(): void
    {
        $ore = new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1), new Quantity(1000));
        $ore->tags->add(ItemTag::Temporary);

        $this->expectException(InvalidArgumentException::class);

        self::inventory()->bank->add($ore);
    }

    #[Test]
    public function it_refuses_items_that_cannot_be_banked(): void
    {
        $potion = new Resource(
            new ItemId(18_927),
            new ItemName('Treasure Potion'),
            new ItemLevel(1),
            new Quantity(20_000),
        );
        $potion->tags->add(ItemTag::CannotBeBanked);
        $inventory = self::inventory()->add($potion, new Quantity(6));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Treasure Potion cannot be banked.');

        $inventory->bank->deposit(new ItemId(18_927));
    }

    #[Test]
    public function it_refuses_more_than_a_stack_holds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::inventory()->bank->add(self::blood(), new Quantity(21));
    }

    #[Test]
    public function an_item_withdrawn_from_the_bank_goes_back_to_the_bag_whole(): void
    {
        $inventory = self::inventory();
        $inventory->bank->add(self::blood(), new Quantity(20));

        $inventory->bank->withdraw(new ItemId(5586));

        static::assertSame(20, $inventory->quantity(new ItemId(5586)));
        static::assertSame(0, $inventory->bank->quantity(new ItemId(5586)));
    }

    #[Test]
    public function an_item_deposited_leaves_the_bag_whole(): void
    {
        $inventory = self::inventory()->add(self::blood(), new Quantity(12));

        $inventory->bank->deposit(new ItemId(5586));

        static::assertSame(0, $inventory->quantity(new ItemId(5586)));
        static::assertSame(12, $inventory->bank->quantity(new ItemId(5586)));
    }

    #[Test]
    public function it_refuses_to_bank_what_the_bag_does_not_keep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::inventory()->bank->deposit(new ItemId(5586));
    }

    #[Test]
    public function it_refuses_to_bank_what_the_hero_equips(): void
    {
        $helm = self::helm();
        $inventory = self::inventory()->add($helm);
        $inventory->equipment->equip($helm);

        $this->expectException(InvalidArgumentException::class);

        $inventory->bank->deposit(new ItemId(4_767));
    }

    #[Test]
    public function it_refuses_to_bank_into_a_full_bank(): void
    {
        $inventory = self::inventory()->add(self::helm());
        $inventory->bank->add(self::blood());

        $this->expectException(InvalidArgumentException::class);

        $inventory->bank->deposit(new ItemId(4_767));
    }

    #[Test]
    public function it_refuses_to_withdraw_what_it_does_not_hold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::inventory()->bank->withdraw(new ItemId(5586));
    }

    #[Test]
    public function it_refuses_to_withdraw_into_a_full_bag(): void
    {
        $inventory = new HeroInventory(new Quantity(1), new Quantity(1))->add(self::helm());
        $inventory->bank->add(self::blood());

        $this->expectException(InvalidArgumentException::class);

        $inventory->bank->withdraw(new ItemId(5586));
    }

    private static function inventory(): HeroInventory
    {
        return new HeroInventory(new Quantity(131), new Quantity(1));
    }

    private static function blood(): Resource
    {
        return new Resource(new ItemId(5586), new ItemName("Elders' Blood"), new ItemLevel(1), new Quantity(20));
    }

    private static function helm(): Helm
    {
        return new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none());
    }
}
