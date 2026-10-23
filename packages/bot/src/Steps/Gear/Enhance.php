<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Gear;

use Generator;
use Lorekeeper\Bot\Abilities\Gear\BestEnhancement;
use Lorekeeper\Bot\Abilities\Gear\EnhancementShops;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Command\Inventory\EnhanceItemCommand;
use Lorekeeper\Client\Command\Shop\LoadShopCommand;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\Equipment\Traits\Enchantable;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\World\Shop\CurrencyShop;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Override;
use Psl\DateTime\Duration;

final readonly class Enhance implements Action
{
    public function __construct(
        public EnhancementType $type,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Enhance the equipped gear with {$this->type->name}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        $shop = EnhancementShops::selling($this->type, $bot->me()->hero()->level);
        if ($shop === null) {
            return new Failed("no shop sells {$this->type->name} enhancements");
        }

        yield from $bot->rest();
        $area = $bot->me()->location()->area;
        $loading = $bot->paced($bot->send(new LoadShopCommand($area, $shop))->awaits(
            Duration::seconds(10),
            ShopLoadedEvent::class,
        ));
        yield from $loading;
        $loaded = $loading->getReturn();
        $selling = $loaded instanceof ShopLoadedEvent ? $loaded->shop : null;
        if (!$selling instanceof CurrencyShop) {
            return new Failed("the {$this->type->name} enhancement shop did not open");
        }

        foreach (self::equipped($bot) as $held) {
            $best = BestEnhancement::for($held, $this->type, $selling, $bot->me()->hero());
            if ($best === null) {
                continue;
            }

            $enhancing = $bot->paced($bot->send(new EnhanceItemCommand($area, $held->id, $best->id, $shop))->awaits(
                Duration::seconds(10),
                ItemsEnhancedEvent::class,
            ));
            yield from $enhancing;
            if (!$enhancing->getReturn() instanceof ItemsEnhancedEvent) {
                return new Failed("the game would not enhance {$held->name->value}");
            }
        }

        return null;
    }

    /** @return list<Equipment&Enchantable> */
    private static function equipped(StepScript $bot): array
    {
        $equipment = $bot->me()->hero()->inventory->equipment;
        $equipped = [];
        foreach (EquipmentSlot::cases() as $slot) {
            $item = $equipment->equipped($slot)->unwrapOr(null);
            if (!$item instanceof Enchantable) {
                continue;
            }

            $equipped[] = $item;
        }

        return $equipped;
    }
}
