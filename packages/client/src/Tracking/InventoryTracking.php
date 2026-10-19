<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Client\Drops;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\AddedItem;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\MoreItemsAddedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;

final readonly class InventoryTracking
{
    public static function follow(
        PlayerInventory $inventory,
        Event $event,
        Identity $me,
        Drops $drops = new Drops(),
    ): PlayerInventory {
        return match (true) {
            $event instanceof DropPickedUpEvent => self::pickedUp($inventory, $event, $drops),
            $event instanceof PlayerInventoryLoadedEvent => new PlayerInventory(
                $event->items,
                $inventory->slots,
                $inventory->temporary,
            ),
            $event instanceof PlayerDataLoadedEvent => self::resized($inventory, PlayerDataTracking::mine($event, $me)),
            $event instanceof TemporaryItemsAddedEvent => new PlayerInventory(
                $inventory->permanent,
                $inventory->slots,
                self::added($inventory->temporary, $event->items),
            ),
            $event instanceof MoreItemsAddedEvent => new PlayerInventory(
                self::grown($inventory->permanent, $event->items),
                $inventory->slots,
                self::grown($inventory->temporary, $event->items),
            ),
            $event instanceof ItemsTurnedInEvent => new PlayerInventory(
                self::taken($inventory->permanent, $event->items),
                $inventory->slots,
                self::taken($inventory->temporary, $event->items),
            ),
            default => $inventory,
        };
    }

    private static function resized(PlayerInventory $inventory, ?PlayerData $data): PlayerInventory
    {
        if ($data === null || $data->bagSlots->value === $inventory->slots->value) {
            return $inventory;
        }

        return new PlayerInventory($inventory->permanent, $data->bagSlots, $inventory->temporary);
    }

    private static function pickedUp(
        PlayerInventory $inventory,
        DropPickedUpEvent $event,
        Drops $drops,
    ): PlayerInventory {
        $item = $drops->find($event->item)->item ?? $inventory->permanent->find($event->item)->unwrapOr(null)?->item;
        if ($item === null) {
            return $inventory;
        }

        $held = $inventory->permanent->items->toArray();
        $held[$event->item->value] = new PlayerItem($item, $event->held);

        return new PlayerInventory(new PlayerItems(array_values($held)), $inventory->slots, $inventory->temporary);
    }

    /** @param non-empty-list<PlayerItem> $added */
    private static function added(PlayerItems $items, array $added): PlayerItems
    {
        $held = $items->items->toArray();
        foreach ($added as $item) {
            $before = $held[$item->item->id->value] ?? null;
            $held[$item->item->id->value] = $before === null
                ? $item
                : new PlayerItem($before->item, new Quantity($before->quantity->value + $item->quantity->value));
        }

        return new PlayerItems(array_values($held));
    }

    /** @param non-empty-list<AddedItem> $more */
    private static function grown(PlayerItems $items, array $more): PlayerItems
    {
        $held = $items->items->toArray();
        foreach ($more as $added) {
            $before = $held[$added->item->value] ?? null;
            if ($before === null) {
                continue;
            }

            $held[$added->item->value] = new PlayerItem(
                $before->item,
                $added->held ?? new Quantity($before->quantity->value + $added->quantity->value),
            );
        }

        return new PlayerItems(array_values($held));
    }

    /** @param non-empty-list<TurnedInItem> $taken */
    private static function taken(PlayerItems $items, array $taken): PlayerItems
    {
        $held = $items->items->toArray();
        foreach ($taken as $item) {
            $before = $held[$item->item->value] ?? null;
            if ($before === null) {
                continue;
            }

            $left = $before->quantity->value - $item->quantity->value;
            if ($left <= 0) {
                unset($held[$item->item->value]);

                continue;
            }

            $held[$item->item->value] = new PlayerItem($before->item, new Quantity($left));
        }

        return new PlayerItems(array_values($held));
    }
}
