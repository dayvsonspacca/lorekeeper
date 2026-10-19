<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;

final readonly class InventoryTracking
{
    public static function follow(PlayerInventory $inventory, Event $event): PlayerInventory
    {
        return match (true) {
            $event instanceof PlayerInventoryLoadedEvent => new PlayerInventory(
                $event->inventory->permanent,
                $inventory->temporary,
            ),
            $event instanceof TemporaryItemsAddedEvent => new PlayerInventory(
                $inventory->permanent,
                self::added($inventory->temporary, $event->items),
            ),
            $event instanceof ItemsTurnedInEvent => new PlayerInventory(
                self::taken($inventory->permanent, $event->items),
                self::taken($inventory->temporary, $event->items),
            ),
            default => $inventory,
        };
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
