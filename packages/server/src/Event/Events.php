<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Item\ItemEquippedEvent;
use Lorekeeper\Server\Event\Item\ItemUnequippedEvent;
use Lorekeeper\Server\Event\Item\ItemUnwornEvent;
use Lorekeeper\Server\Event\Item\ItemWornEvent;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;

final readonly class Events
{
    /** @var non-empty-list<class-string<Event>> */
    public const array ALL = [
        ActionTooSoonEvent::class,
        AwayFromKeyboardEvent::class,
        ClassChangedEvent::class,
        ConnectionEstablishedEvent::class,
        PlayerInventoryLoadedEvent::class,
        ItemEquippedEvent::class,
        ItemUnequippedEvent::class,
        ItemUnwornEvent::class,
        ItemWornEvent::class,
        LoginFailedEvent::class,
        LoginSucceededEvent::class,
        AlreadyInMapEvent::class,
        MapLockedEvent::class,
        MapMemberOnlyEvent::class,
        MapNotAvailableEvent::class,
        MapRequiresQuestEvent::class,
        MovedToMapEvent::class,
        QuestLoadedEvent::class,
        ShopLoadedEvent::class,
    ];

    /** @return list<Event|MalformedEventException> */
    public static function recognize(Message $message): array
    {
        $recognized = [];

        foreach (self::ALL as $event) {
            try {
                /** @mago-expect analysis:possibly-static-access-on-interface */
                $heard = $event::from($message);
            } catch (MalformedEventException $malformed) {
                $recognized[] = $malformed;

                continue;
            }

            if ($heard->isSome()) {
                $recognized[] = $heard->unwrap();
            }
        }

        return $recognized;
    }
}
