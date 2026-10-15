<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Closure;
use Lorekeeper\Protocol\Event\ActionTooSoonEvent;
use Lorekeeper\Protocol\Event\AwayFromKeyboardEvent;
use Lorekeeper\Protocol\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Protocol\Event\ConnectionEstablishedEvent;
use Lorekeeper\Protocol\Event\Event;
use Lorekeeper\Protocol\Event\Item\ItemEquippedEvent;
use Lorekeeper\Protocol\Event\Item\ItemUnequippedEvent;
use Lorekeeper\Protocol\Event\Item\ItemUnwornEvent;
use Lorekeeper\Protocol\Event\Item\ItemWornEvent;
use Lorekeeper\Protocol\Event\LoginFailedEvent;
use Lorekeeper\Protocol\Event\LoginSucceededEvent;
use Lorekeeper\Protocol\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Protocol\Event\Map\MapLockedEvent;
use Lorekeeper\Protocol\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Protocol\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Protocol\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\Event\PlayerInventoryLoadedEvent;
use Lorekeeper\Protocol\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Protocol\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Protocol\Message\Message;
use Psl\Option\Option;

final readonly class KnownEvents
{
    /** @return list<Closure(Message): Option<Event>> */
    public static function recognizers(): array
    {
        return [
            ConnectionEstablishedEvent::from(...),
            LoginSucceededEvent::from(...),
            LoginFailedEvent::from(...),
            AwayFromKeyboardEvent::from(...),
            ActionTooSoonEvent::from(...),
            MovedToMapEvent::from(...),
            AlreadyInMapEvent::from(...),
            MapLockedEvent::from(...),
            MapMemberOnlyEvent::from(...),
            MapNotAvailableEvent::from(...),
            MapRequiresQuestEvent::from(...),
            PlayerInventoryLoadedEvent::from(...),
            ItemEquippedEvent::from(...),
            ItemWornEvent::from(...),
            ItemUnwornEvent::from(...),
            ItemUnequippedEvent::from(...),
            ClassChangedEvent::from(...),
            QuestLoadedEvent::from(...),
            ShopLoadedEvent::from(...),
        ];
    }
}
