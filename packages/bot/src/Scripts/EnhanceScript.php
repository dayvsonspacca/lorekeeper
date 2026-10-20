<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Scripts;

use Closure;
use Generator;
use Lorekeeper\Bot\Abilities\Gear\BestEnhancement;
use Lorekeeper\Bot\Abilities\Gear\EnhancementShops;
use Lorekeeper\Bot\Abilities\Paces;
use Lorekeeper\Bot\BotScript;
use Lorekeeper\Client\Command\Inventory\EnhanceItemCommand;
use Lorekeeper\Client\Command\Shop\LoadShopCommand;
use Lorekeeper\Client\Script\Done;
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
use Psl\DateTime\Timestamp;

final class EnhanceScript extends BotScript
{
    use Paces;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(
        private readonly EnhancementType $type,
        ?Closure $clock = null,
    ) {
        parent::__construct($clock);
    }

    #[Override]
    public function title(): string
    {
        return "Enhance the equipped gear with {$this->type->name}";
    }

    #[Override]
    protected function rules(): Generator
    {
        $shop = EnhancementShops::selling($this->type, $this->me()->player()->progress->level);
        if ($shop === null) {
            return new Failed("no shop sells {$this->type->name} enhancements");
        }

        $area = $this->me()->location()->area;
        $loading = $this->paced($this->send(new LoadShopCommand($area, $shop))->awaits(
            Duration::seconds(10),
            ShopLoadedEvent::class,
        ));
        yield from $loading;
        $loaded = $loading->getReturn();
        if (!$loaded instanceof ShopLoadedEvent || !$loaded->shop instanceof CurrencyShop) {
            return new Failed("the {$this->type->name} enhancement shop did not open");
        }

        $offer = array_values($loaded->shop->inventory->items->toArray());
        $enhanced = [];
        foreach ($this->equipped() as $held) {
            $best = BestEnhancement::for($held, $this->type, $offer, $this->me()->player());
            if ($best === null) {
                continue;
            }

            $enhancing = $this->paced($this->send(
                new EnhanceItemCommand($area, $held->item->id, $best->item->id, $shop),
            )->awaits(Duration::seconds(10), ItemsEnhancedEvent::class));
            yield from $enhancing;
            $answer = $enhancing->getReturn();
            if (!$answer instanceof ItemsEnhancedEvent) {
                return new Failed("the game would not enhance {$held->item->name->value}");
            }

            $enhanced[] = "{$held->item->name->value} (level {$answer->enhancement->level->value})";
        }

        return new Done(
            $enhanced === []
                ? "the equipped gear already has the best {$this->type->name} enhancements it can get"
                : "enhanced with {$this->type->name}: " . implode(', ', $enhanced),
        );
    }

    /** @return list<PlayerItem> */
    private function equipped(): array
    {
        return array_values(array_filter(
            $this
                ->me()
                ->player()
                ->inventory->permanent->items->toArray(),
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
