<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Gear;

use Lorekeeper\Bot\Abilities\Gear\BestEnhancement;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Shop\CurrencyShopItem;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class BestEnhancementTest extends TestCase
{
    #[Test]
    public function gear_the_bot_does_not_enhance_is_never_offered_an_enhancement(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(
            new MonsterRewardedEvent(new Gold(35_178), new Experience(0), new ClassPoints(0), new MonsterSpawnId(1)),
        );
        $amulet = new PlayerItem(
            new Necklace(new ItemId(22_884), new ItemName('Gold PvP Amulet +1500'), new ItemLevel(20)),
        );
        $sold = new CurrencyShopItem(
            new EnhancementItem(
                new ItemId(3723),
                new ItemName('Lucky Armor Enhancement'),
                new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusOne, new ItemLevel(26), Option\none()),
                EquipmentSlot::Necklace,
            ),
            new ItemPrice(new Gold(1260)),
        );

        static::assertNull(BestEnhancement::for($amulet, EnhancementType::Lucky, [$sold], $me->player()));
    }
}
