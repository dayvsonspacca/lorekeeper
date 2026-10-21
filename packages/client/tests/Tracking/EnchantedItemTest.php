<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\EnchantedItem;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Item\Misc\Resource;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class EnchantedItemTest extends TestCase
{
    #[Test]
    public function a_class_a_helm_and_a_cape_take_the_enhancement(): void
    {
        $lucky = self::lucky();
        $class = EnchantedItem::from(
            new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none()),
            $lucky,
        );
        $helm = EnchantedItem::from(
            new Helm(new ItemId(1), new ItemName('Helm'), new ItemLevel(1), Option\none()),
            $lucky,
        );
        $cape = EnchantedItem::from(
            new Cape(new ItemId(2), new ItemName('Cape'), new ItemLevel(1), Option\none()),
            $lucky,
        );

        static::assertInstanceOf(ClassItem::class, $class);
        static::assertInstanceOf(Helm::class, $helm);
        static::assertInstanceOf(Cape::class, $cape);
        static::assertSame($lucky, $class->enhancement->unwrap());
        static::assertSame($lucky, $helm->enhancement->unwrap());
        static::assertSame($lucky, $cape->enhancement->unwrap());
    }

    #[Test]
    public function an_enhanced_item_keeps_its_tags(): void
    {
        $helm = new Helm(new ItemId(1), new ItemName('Helm'), new ItemLevel(1), Option\none());
        $helm->tags->add(ItemTag::MemberOnly);

        static::assertTrue(EnchantedItem::from($helm, self::lucky())->tags->has(ItemTag::MemberOnly));
    }

    #[Test]
    public function an_item_that_cannot_be_enhanced_stays_as_it_is(): void
    {
        $ore = new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1));

        static::assertSame($ore, EnchantedItem::from($ore, self::lucky()));
    }

    private static function lucky(): Enhancement
    {
        return new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none());
    }
}
