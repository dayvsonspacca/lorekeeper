<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Gear;

use Lorekeeper\Bot\Abilities\Gear\BestEnhancement;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Shop\CurrencyShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class BestEnhancementTest extends TestCase
{
    #[Test]
    public function an_enhancement_for_another_slot_is_never_offered(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(
            new MonsterRewardedEvent(new Gold(35_178), new Experience(0), new ClassPoints(0), new MonsterSpawnId(1)),
        );
        $staff = new Weapon(
            new ItemId(3),
            new ItemName('Default Staff'),
            new ItemLevel(1),
            WeaponType::Staff,
            new WeaponDamage(new DamageVariance(10)),
            Option\none(),
        );
        /** @var Inventory<Item> $sold */
        $sold = new Inventory([new EnhancementItem(
            new ItemId(3723),
            new ItemName('Lucky Armor Enhancement'),
            new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusOne, new ItemLevel(26), Option\none()),
            EquipmentSlot::Armor,
        )]);
        $shop = new CurrencyShop(new ShopId(147), new ShopName('Lucky Enhancement Shop'), $sold, [
            3723 => new ItemPrice(new Gold(1260)),
        ]);

        static::assertNull(BestEnhancement::for($staff, EnhancementType::Lucky, $shop, $me->hero()));
    }
}
