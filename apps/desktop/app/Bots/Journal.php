<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Client\Character;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestTurnInRefusedEvent;

final class Journal
{
    private const int EVERY = 10;

    /** @var array<int, string> */
    private array $quests = [];

    /** @var array<int, string> */
    private array $items = [];

    /** @return list<array{string, LogLevel}> */
    public function heard(Event $event, ?Character $me): array
    {
        return match (true) {
            $event instanceof QuestLoadedEvent => $this->learnQuests($event),
            $event instanceof ItemDroppedEvent => $this->learnItems($event->items),
            $event instanceof QuestAcceptedEvent => [["Accepted {$this->quest($event->quest)}", LogLevel::Info]],
            $event instanceof QuestCompletedEvent => [["Completed {$event->name->value}", LogLevel::Success]],
            $event instanceof QuestTurnInRefusedEvent => [[
                "Could not complete {$event->name->value}" . ($event->reason === '' ? '' : ": {$event->reason}"),
                LogLevel::Warning,
            ]],
            $event instanceof DropPickedUpEvent => [[
                "Picked up {$this->item($event->item, $me)} ×{$event->picked->value} ({$event->held->value} held)",
                LogLevel::Success,
            ]],
            $event instanceof ItemsIncreasedEvent => array_map(fn(ItemIncrease $got): array => [
                "Got {$this->item($got->item, $me)} ×{$got->quantity->value} ({$this->held($got, $me)} held)",
                LogLevel::Success,
            ], $event->items),
            $event instanceof QuestItemsReceivedEvent => $this->progress([...$this->learnItemIds($event->items)], $me),
            $event instanceof QuestItemsIncreasedEvent => $this->progress(
                array_map(static fn(ItemIncrease $got): ItemId => $got->item, $event->items),
                $me,
            ),
            default => [],
        };
    }

    /** @return list<array{string, LogLevel}> */
    private function learnQuests(QuestLoadedEvent $event): array
    {
        foreach ($event->quests as $quest) {
            $this->quests[$quest->id->value] = $quest->name->value;
        }

        return [];
    }

    /**
     * @param list<PlayerItem> $items
     *
     * @return list<array{string, LogLevel}>
     */
    private function learnItems(array $items): array
    {
        $this->learnItemIds($items);

        return [];
    }

    /**
     * @param list<PlayerItem> $items
     *
     * @return list<ItemId>
     */
    private function learnItemIds(array $items): array
    {
        $ids = [];
        foreach ($items as $held) {
            $this->items[$held->item->id->value] = $held->item->name->value;
            $ids[] = $held->item->id;
        }

        return $ids;
    }

    /**
     * @param list<ItemId> $items
     *
     * @return list<array{string, LogLevel}>
     */
    private function progress(array $items, ?Character $me): array
    {
        $lines = [];
        foreach ($items as $item) {
            $held = $me?->player()->inventory->quantity($item) ?? 0;
            if ($held !== 1 && ($held % self::EVERY) !== 0) {
                continue;
            }

            $lines[] = ["Collected {$this->item($item, $me)} ×{$held}", LogLevel::Info];
        }

        return $lines;
    }

    private function quest(QuestId $quest): string
    {
        return $this->quests[$quest->value] ?? "quest {$quest->value}";
    }

    private function item(ItemId $item, ?Character $me): string
    {
        $named = $this->items[$item->value] ?? "item {$item->value}";
        if ($me === null) {
            return $named;
        }

        return $me
            ->player()
            ->inventory->find($item)->map(static fn(PlayerItem $held): string => $held->item->name->value)->unwrapOr(
                $named,
            );
    }

    private function held(ItemIncrease $got, ?Character $me): int
    {
        return $got->held->value ?? $me?->player()->inventory->quantity($got->item) ?? $got->quantity->value;
    }
}
