<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Log;

use Lorekeeper\Server\Event\ActionTooSoonEvent;
use Lorekeeper\Server\Event\AwayFromKeyboardEvent;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Item\ItemEquippedEvent;
use Lorekeeper\Server\Event\Item\ItemUnequippedEvent;
use Lorekeeper\Server\Event\Item\ItemUnwornEvent;
use Lorekeeper\Server\Event\Item\ItemWornEvent;
use Lorekeeper\Server\Event\LoginFailedEvent;
use Lorekeeper\Server\Event\LoginSucceededEvent;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\RoomJoinedEvent;
use Lorekeeper\Server\Event\Player\PlayerEnteredEvent;
use Lorekeeper\Server\Event\Player\PlayerLeftEvent;
use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedQuest;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;

final readonly class EventSummary
{
    public static function name(Event $event): string
    {
        $class = $event::class;

        return (string) preg_replace(
            '/Event$/',
            replacement: '',
            subject: substr($class, (int) strrpos($class, needle: '\\') + 1),
        );
    }

    public static function of(Event $event): string
    {
        return match (true) {
            $event instanceof ConnectionEstablishedEvent => 'Connection established',
            $event instanceof LoginSucceededEvent => "Logged in as socket {$event->socketId->value}",
            $event instanceof LoginFailedEvent => 'Login refused',
            $event instanceof ActionTooSoonEvent => 'Action too soon',
            $event instanceof AwayFromKeyboardEvent => 'Away from keyboard',
            $event instanceof ClassChangedEvent
                => "Socket {$event->player->value} is {$event->className->value} ({$event->category->name}, {$event->points->value} CP)",
            $event instanceof ItemEquippedEvent
                => "Socket {$event->player->value} equipped item {$event->itemId->value}",
            $event instanceof ItemUnequippedEvent
                => "Socket {$event->player->value} unequipped item {$event->itemId->value}",
            $event instanceof ItemWornEvent => "Socket {$event->player->value} wore item {$event->itemId->value}",
            $event instanceof ItemUnwornEvent => "Socket {$event->player->value} took off item {$event->itemId->value}",
            $event instanceof PlayerInventoryLoadedEvent => 'Inventory loaded: ' . count($event->items) . ' items',
            $event instanceof AlreadyInMapEvent => 'Already in that map',
            $event instanceof MapLockedEvent => 'The map is locked',
            $event instanceof MapMemberOnlyEvent => "{$event->mapName->value} is member-only",
            $event instanceof MapNotAvailableEvent => "{$event->mapName->value} is not available",
            $event instanceof MapRequiresQuestEvent => 'That map requires special quest actions',
            $event instanceof MovedToMapEvent => self::moved($event),
            $event instanceof RoomJoinedEvent => 'Joined a room with ' . count($event->players) . ' players',
            $event instanceof PlayerEnteredEvent => "{$event->name->value} (socket {$event->socketId->value}) entered",
            $event instanceof PlayerLeftEvent => "{$event->name->value} (socket {$event->socketId->value}) left",
            $event instanceof PlayerUpdatedEvent => "{$event->name->value} updated",
            $event instanceof QuestLoadedEvent => self::quests($event),
            $event instanceof ShopLoadedEvent => "{$event->name->value}: " . count($event->items) . ' items',
            default => self::name($event),
        };
    }

    public static function moved(MovedToMapEvent $event): string
    {
        return sprintf(
            '%s (room %d) · %d monsters, %d players',
            $event->map->value,
            $event->roomId->value,
            count($event->monsters),
            count($event->players),
        );
    }

    private static function quests(QuestLoadedEvent $event): string
    {
        $names = array_map(static fn(QuestLoadedQuest $quest): string => $quest->name->value, $event->quests);

        return count($names) . ' quests: ' . implode(', ', $names);
    }
}
