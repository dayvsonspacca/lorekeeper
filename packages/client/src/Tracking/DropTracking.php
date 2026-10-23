<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Client\Drops;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\HeldItem;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;

final readonly class DropTracking
{
    public static function follow(Drops $drops, Event $event): Drops
    {
        return match (true) {
            $event instanceof ItemDroppedEvent => self::dropped($drops, $event->items),
            $event instanceof DropPickedUpEvent && $drops->find($event->item) !== null => new Drops(array_diff_key(
                $drops->items,
                [$event->item->value => true],
            )),
            default => $drops,
        };
    }

    /** @param non-empty-list<HeldItem> $dropped */
    private static function dropped(Drops $drops, array $dropped): Drops
    {
        $items = $drops->items;
        foreach ($dropped as $item) {
            $before = $items[$item->item->id->value] ?? null;
            $items[$item->item->id->value] = $before === null
                ? $item
                : new HeldItem($before->item, new Quantity($before->quantity->value + $item->quantity->value));
        }

        return new Drops($items);
    }
}
