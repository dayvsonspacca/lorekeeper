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
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerItem;
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
        $shop = EnhancementShops::selling($this->type, $bot->me()->player()->progress->level);
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
        if (!$loaded instanceof ShopLoadedEvent || !$loaded->shop instanceof CurrencyShop) {
            return new Failed("the {$this->type->name} enhancement shop did not open");
        }

        $offer = array_values($loaded->shop->inventory->items->toArray());
        foreach (self::equipped($bot) as $held) {
            $best = BestEnhancement::for($held, $this->type, $offer, $bot->me()->player());
            if ($best === null) {
                continue;
            }

            $enhancing = $bot->paced($bot->send(
                new EnhanceItemCommand($area, $held->item->id, $best->item->id, $shop),
            )->awaits(Duration::seconds(10), ItemsEnhancedEvent::class));
            yield from $enhancing;
            if (!$enhancing->getReturn() instanceof ItemsEnhancedEvent) {
                return new Failed("the game would not enhance {$held->item->name->value}");
            }
        }

        return null;
    }

    /** @return list<PlayerItem> */
    private static function equipped(StepScript $bot): array
    {
        return array_values(array_filter(
            $bot->me()->player()->inventory->permanent->items->toArray(),
            static fn(PlayerItem $held): bool => (
                $held->equipped
                && (
                    $held->item instanceof Weapon
                    || $held->item instanceof ClassItem
                    || $held->item instanceof Helm
                    || $held->item instanceof Cape
                )
            ),
        ));
    }
}
