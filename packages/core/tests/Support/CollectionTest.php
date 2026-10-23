<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use InvalidArgumentException;
use Lorekeeper\Core\Item\ItemId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    #[Test]
    public function it_finds_an_item_by_its_key(): void
    {
        $scales = new ItemId(11_475);
        $ids = new ItemIds([new ItemId(15_651), $scales]);

        static::assertCount(2, $ids);
        static::assertSame($scales, $ids->find(new ItemId(11_475))->unwrap());
        static::assertTrue($ids->find(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_has_only_the_items_whose_key_it_holds(): void
    {
        $ids = new ItemIds([new ItemId(15_651)]);

        static::assertTrue($ids->has(new ItemId(15_651)));
        static::assertFalse($ids->has(new ItemId(11_475)));
    }

    #[Test]
    public function it_iterates_over_its_items_by_key(): void
    {
        $healer = new ItemId(15_651);
        $scales = new ItemId(11_475);

        static::assertSame([15_651 => $healer, 11_475 => $scales], iterator_to_array(new ItemIds([$healer, $scales])));
    }

    #[Test]
    public function it_rejects_the_same_key_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ItemIds([new ItemId(15_651), new ItemId(15_651)]);
    }

    #[Test]
    public function it_adds_an_item(): void
    {
        $ids = new ItemIds([new ItemId(15_651)]);

        static::assertSame($ids, $ids->add(new ItemId(11_475)));
        static::assertCount(2, $ids);
        static::assertTrue($ids->find(new ItemId(11_475))->isSome());
    }

    #[Test]
    public function it_rejects_adding_a_key_it_already_has(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ItemIds([new ItemId(15_651)])->add(new ItemId(15_651));
    }

    #[Test]
    public function it_removes_an_item(): void
    {
        $ids = new ItemIds([new ItemId(15_651), new ItemId(11_475)]);

        static::assertSame($ids, $ids->remove(new ItemId(15_651)));
        static::assertCount(1, $ids);
        static::assertTrue($ids->find(new ItemId(15_651))->isNone());
    }

    #[Test]
    public function it_rejects_removing_a_key_it_does_not_have(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ItemIds([new ItemId(15_651)])->remove(new ItemId(1));
    }

    #[Test]
    public function it_keeps_only_the_items_that_match(): void
    {
        $ids = new ItemIds([new ItemId(15_651), new ItemId(11_475), new ItemId(2)])->filter(
            static fn(ItemId $id): bool => $id->value > 10_000,
        );

        static::assertSame([15_651, 11_475], array_keys(iterator_to_array($ids)));
    }
}
