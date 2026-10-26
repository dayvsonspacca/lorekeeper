<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use InvalidArgumentException;
use Lorekeeper\Client\Drops;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\Equipment\Traits\Enchantable;
use Lorekeeper\Core\Item\Stock;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\HeldItem;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Player\Hero\HeroData;
use Lorekeeper\Server\Event\Player\Hero\HeroDataLoadedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final readonly class InventoryTracking
{
    /** @throws InvalidArgumentException */
    public static function follow(
        HeroInventory $inventory,
        Event $event,
        Identity $me,
        Drops $drops = new Drops(),
    ): bool {
        return match (true) {
            $event instanceof InventoryLoadedEvent => self::loaded($inventory, $event->items),
            $event instanceof HeroDataLoadedEvent => self::resized($inventory, HeroDataTracking::mine($event, $me)),
            $event instanceof QuestItemsReceivedEvent => self::received($inventory, $event->items),
            $event instanceof ItemsIncreasedEvent => self::grown($inventory->permanent, $event->items),
            $event instanceof QuestItemsIncreasedEvent => self::grown($inventory->temporary, $event->items),
            $event instanceof ItemsEnhancedEvent => self::enhanced($inventory, $event),
            $event instanceof ItemsTurnedInEvent => self::taken($inventory, $event->items),
            $event instanceof DropPickedUpEvent => self::pickedUp($inventory, $event, $drops),
            default => false,
        };
    }

    /**
     * @param list<HeldItem> $items
     *
     * @throws InvalidArgumentException
     */
    public static function loaded(HeroInventory $inventory, array $items): bool
    {
        foreach (iterator_to_array($inventory->permanent) as $kept) {
            $inventory->take($kept->id, new Quantity($inventory->quantity($kept->id)));
        }

        foreach ($items as $held) {
            $inventory->add($held->item, $held->quantity);
            if (!$held->item instanceof Equipment) {
                continue;
            }

            if ($held->equipped) {
                $inventory->equipment->equip($held->item);
            }

            if ($held->worn) {
                $inventory->costume->wear($held->item);
            }
        }

        return true;
    }

    private static function resized(HeroInventory $inventory, ?HeroData $data): bool
    {
        if ($data === null) {
            return false;
        }

        $changed = !$data->bagSlots->equals($inventory->slots) || !$data->bankSlots->equals($inventory->bank->slots);
        $inventory->resize($data->bagSlots);
        $inventory->bank->resize($data->bankSlots);

        return $changed;
    }

    /**
     * @param non-empty-list<HeldItem> $items
     *
     * @throws InvalidArgumentException
     */
    private static function received(HeroInventory $inventory, array $items): bool
    {
        foreach ($items as $held) {
            $inventory->receive($held->item, $held->quantity);
        }

        return true;
    }

    /**
     * @param non-empty-list<ItemIncrease> $more
     *
     * @throws InvalidArgumentException
     */
    private static function grown(Stock $stock, array $more): bool
    {
        $changed = false;
        foreach ($more as $added) {
            $item = $stock->find($added->item)->unwrapOr(null);
            if ($item === null) {
                continue;
            }

            $changed = true;
            if ($added->held !== null) {
                $stock->hold($added->item, $added->held);
                continue;
            }

            $stock->receive($item, $added->quantity);
        }

        return $changed;
    }

    private static function enhanced(HeroInventory $inventory, ItemsEnhancedEvent $event): bool
    {
        $changed = false;
        foreach ($event->items as $id) {
            $item = $inventory->permanent->find($id)->unwrapOr(null);
            if (!$item instanceof Enchantable) {
                continue;
            }

            $item->enchant($event->enhancement);
            $changed = true;
        }

        return $changed;
    }

    /**
     * @param non-empty-list<TurnedInItem> $taken
     *
     * @throws InvalidArgumentException
     */
    private static function taken(HeroInventory $inventory, array $taken): bool
    {
        $changed = false;
        foreach ($taken as $item) {
            if ($inventory->find($item->item)->isNone()) {
                continue;
            }

            $inventory->take($item->item, $item->quantity);
            $changed = true;
        }

        return $changed;
    }

    /** @throws InvalidArgumentException */
    private static function pickedUp(HeroInventory $inventory, DropPickedUpEvent $event, Drops $drops): bool
    {
        if ($inventory->permanent->find($event->item)->isSome()) {
            $inventory->permanent->hold($event->item, $event->held);

            return true;
        }

        $dropped = $drops->find($event->item);
        if ($dropped === null) {
            return false;
        }

        $inventory->add($dropped->item, $event->held);

        return true;
    }
}
