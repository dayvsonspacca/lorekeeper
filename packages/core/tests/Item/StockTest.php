<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Item\Stock;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class StockTest extends TestCase
{
    #[Test]
    public function it_holds_items_with_how_many_of_each(): void
    {
        $scales = self::scales();
        $helm = self::helm();
        $stock = new Stock()->add($scales, new Quantity(50))->add($helm);

        static::assertCount(2, $stock);
        static::assertSame([11_475 => $scales, 4_767 => $helm], iterator_to_array($stock));
        static::assertSame(50, $stock->quantity(new ItemId(11_475)));
        static::assertSame(1, $stock->quantity(new ItemId(4_767)));
        static::assertSame(0, $stock->quantity(new ItemId(1)));
    }

    #[Test]
    public function it_refuses_an_item_it_already_holds_without_touching_it(): void
    {
        $stock = new Stock()->add(self::scales(), new Quantity(50));

        try {
            $stock->add(self::scales(), new Quantity(10));
            static::fail('The same item was held twice.');
        } catch (InvalidArgumentException) {
            static::assertSame(50, $stock->quantity(new ItemId(11_475)));
        }
    }

    #[Test]
    public function it_refuses_more_than_a_stack_holds_without_keeping_the_item(): void
    {
        $stock = new Stock();

        try {
            $stock->add(self::scales(), new Quantity(1001));
            static::fail('A stack went past its limit.');
        } catch (InvalidArgumentException) {
            static::assertCount(0, $stock);
        }
    }

    #[Test]
    public function it_receives_a_new_item_or_more_of_one_it_holds_up_to_the_stack(): void
    {
        $stock = new Stock()
            ->receive(self::scales(), new Quantity(990))
            ->receive(self::scales(), new Quantity(10));

        static::assertSame(1000, $stock->quantity(new ItemId(11_475)));

        $this->expectException(InvalidArgumentException::class);

        $stock->receive(self::scales());
    }

    #[Test]
    public function it_takes_part_of_a_stack_and_drops_the_item_once_none_is_left(): void
    {
        $stock = new Stock()->add(self::scales(), new Quantity(50))->take(new ItemId(11_475), new Quantity(20));

        static::assertSame(30, $stock->quantity(new ItemId(11_475)));

        $stock->take(new ItemId(11_475), new Quantity(99));

        static::assertTrue($stock->find(new ItemId(11_475))->isNone());
    }

    #[Test]
    public function it_refuses_to_take_what_it_does_not_hold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Stock()->take(new ItemId(11_475), new Quantity(1));
    }

    #[Test]
    public function it_takes_the_exact_amount_the_server_says_it_holds(): void
    {
        $stock = new Stock()->add(self::scales(), new Quantity(50))->add(self::helm());

        $stock->hold(new ItemId(11_475), new Quantity(320));
        $stock->hold(new ItemId(4_767), new Quantity(1));

        static::assertSame(320, $stock->quantity(new ItemId(11_475)));
        static::assertSame(1, $stock->quantity(new ItemId(4_767)));
    }

    #[Test]
    public function it_refuses_to_hold_more_than_a_stack_or_an_item_it_does_not_have(): void
    {
        $stock = new Stock()->add(self::scales());

        try {
            $stock->hold(new ItemId(11_475), new Quantity(1001));
            static::fail('A stack went past its limit.');
        } catch (InvalidArgumentException) {
            static::assertSame(1, $stock->quantity(new ItemId(11_475)));
        }

        $this->expectException(InvalidArgumentException::class);

        $stock->hold(new ItemId(1), new Quantity(1));
    }

    private static function scales(): Resource
    {
        return new Resource(new ItemId(11_475), new ItemName('Dragon Scale'), new ItemLevel(1), new Quantity(1000));
    }

    private static function helm(): Helm
    {
        return new Helm(new ItemId(4_767), new ItemName('Helm'), new ItemLevel(1), Option\none());
    }
}
