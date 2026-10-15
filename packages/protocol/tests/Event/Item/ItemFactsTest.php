<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Item;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\ItemType;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\Item\ItemFacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemFactsTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, list<object>}> */
    public static function items(): iterable
    {
        yield 'name only' => [
            ['sName' => 'Chaoroot'],
            [new ItemName('Chaoroot')],
        ];
        yield 'description' => [
            ['sName' => 'Sweeping Broom', 'sDesc' => "If you /use this broom you'll be sweeping the floor!"],
            [
                new ItemName('Sweeping Broom'),
                new ItemDescription("If you /use this broom you'll be sweeping the floor!"),
            ],
        ];
        yield 'blank description' => [
            ['sName' => 'Special Hair Gel', 'sDesc' => ' '],
            [new ItemName('Special Hair Gel')],
        ];
        yield 'empty description' => [
            ['sName' => 'Sweeping Broom', 'sDesc' => ''],
            [new ItemName('Sweeping Broom')],
        ];
        yield 'gold price' => [
            ['sName' => 'Gold PvP Amulet +1500', 'iCost' => 26_000, 'bCoins' => false],
            [new ItemName('Gold PvP Amulet +1500'), new ItemPrice(new Gold(26_000))],
        ];
        yield 'adventure coins price' => [
            ['sName' => 'Crag & Bamboozle', 'iCost' => 5000, 'bCoins' => true],
            [new ItemName('Crag & Bamboozle'), new ItemPrice(new AdventureCoins(5000))],
        ];
        yield 'zero price' => [
            ['sName' => 'Blinding Light of Destiny', 'iCost' => 0, 'bCoins' => true],
            [new ItemName('Blinding Light of Destiny')],
        ];
        yield 'gamefile and linkage' => [
            ['sName' => 'Unidentified 1', 'sFile' => 'items/guns/MiltonPoolgun01.swf', 'sLink' => 'MiltonPoolgun01'],
            [
                new ItemName('Unidentified 1'),
                new Gamefile('items/guns/MiltonPoolgun01.swf'),
                new Linkage('MiltonPoolgun01'),
            ],
        ];
        yield 'gamefile without linkage' => [
            ['sName' => 'Sweeping Broom', 'sFile' => 'items/staves/staff10a.swf', 'sLink' => ''],
            [new ItemName('Sweeping Broom'), new Gamefile('items/staves/staff10a.swf')],
        ];
        yield 'type and slot' => [
            ['sName' => 'Blinding Light of Destiny', 'sType' => 'Axe', 'sES' => 'Weapon'],
            [new ItemName('Blinding Light of Destiny'), new ItemType('Axe'), EquipmentSlot::Weapon],
        ];
        yield 'class in the armor slot' => [
            ['sName' => 'ArchPaladin', 'sType' => 'Class', 'sES' => 'ar'],
            [new ItemName('ArchPaladin'), new ItemType('Class'), EquipmentSlot::Armor],
        ];
        yield 'item without slot' => [
            ['sName' => 'Bone Dust', 'sType' => 'Quest Item', 'sES' => 'None'],
            [new ItemName('Bone Dust'), new ItemType('Quest Item')],
        ];
        yield 'file that is not swf' => [
            ['sName' => "Elders' Blood", 'sFile' => 'icb3', 'sLink' => 'potion'],
            [new ItemName("Elders' Blood")],
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @param list<object> $values
     */
    #[Test]
    #[DataProvider('items')]
    public function it_extracts_item_facts(array $item, array $values): void
    {
        static::assertEquals(
            array_map(static fn(object $value): Fact => new Fact(new ItemId(447), $value), $values),
            ItemFacts::of(['ItemID' => 447, ...$item]),
        );
    }
}
