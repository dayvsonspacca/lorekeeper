<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Facts;

use Lorekeeper\Apps\Wiki\Facts\Fact;
use Lorekeeper\Apps\Wiki\Facts\Kind;
use Lorekeeper\Apps\Wiki\Facts\Reading\Entry;
use Lorekeeper\Apps\Wiki\Facts\Reading\ItemFacts;
use Lorekeeper\Apps\Wiki\Tests\GameMessages;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use PHPUnit\Framework\TestCase;

final class ItemFactsTest extends TestCase
{
    use GameMessages;

    public function testAnArmorTellsItsNameAndThatItIsAnArmor(): void
    {
        $facts = ItemFacts::of(new Entry(self::gladiator()));

        static::assertEquals(
            [
                new Fact(new ItemId(16_416), new ItemName('Gladiator of Circumstance')),
                new Fact(new ItemId(16_416), new Kind(Armor::class)),
            ],
            $facts,
        );
    }

    public function testATaggedItemTellsItsTags(): void
    {
        $values = self::values(ItemFacts::of(new Entry([...self::gladiator(), 'bUpg' => true, 'bTemp' => 1])));

        static::assertContains(ItemTag::MemberOnly, $values);
        static::assertContains(ItemTag::Temporary, $values);
    }

    public function testAKindNotKeptYetTellsOnlyTheRest(): void
    {
        static::assertEquals([new ItemName('Ruby Pendant')], self::values(ItemFacts::of(new Entry(self::pendant()))));
    }

    public function testABlankNameIsLeftUntold(): void
    {
        static::assertEquals(
            [new Kind(Armor::class)],
            self::values(ItemFacts::of(new Entry([...self::gladiator(), 'sName' => '  ']))),
        );
    }

    public function testAnIdGivenAsTextIsRead(): void
    {
        static::assertEquals(
            new ItemId(42),
            ItemFacts::of(new Entry(['ItemID' => '42', 'sName' => 'Rock']))[0]->subject,
        );
    }

    public function testAnEntryWithoutAnIdTellsNothing(): void
    {
        static::assertSame([], ItemFacts::of(new Entry(['sName' => 'Nameless'])));
        static::assertSame([], ItemFacts::of(new Entry(['ItemID' => 0, 'sName' => 'Zero'])));
        static::assertSame([], ItemFacts::of(new Entry(['ItemID' => 'one', 'sName' => 'Word'])));
    }

    /**
     * @param list<Fact<object, object>> $facts
     *
     * @return list<object>
     */
    private static function values(array $facts): array
    {
        return array_map(static fn(Fact $fact): object => $fact->value, $facts);
    }
}
