<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\AttackRejectedEvent;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Inventory\MoreItemsAddedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
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
use Lorekeeper\Server\Event\Map\RoomJoinedEvent;
use Lorekeeper\Server\Event\Player\PlayerAwayUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Player\PlayerEnteredEvent;
use Lorekeeper\Server\Event\Player\PlayerLeftEvent;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Event\Player\StatsUpdatedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Quest\QuestTurnInRefusedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;

final readonly class Events
{
    /** @var non-empty-list<class-string<Event>> */
    public const array ALL = [
        ActionTooSoonEvent::class,
        AttackRejectedEvent::class,
        ClassChangedEvent::class,
        MonsterRespawnedEvent::class,
        MonsterRewardedEvent::class,
        MonstersDiedEvent::class,
        MonstersHealthChangedEvent::class,
        LeveledUpEvent::class,
        PlayersHealthChangedEvent::class,
        PlayersManaChangedEvent::class,
        PlayersStateChangedEvent::class,
        ResurrectedEvent::class,
        SkillsLoadedEvent::class,
        ConnectionEstablishedEvent::class,
        PlayerInventoryLoadedEvent::class,
        TemporaryItemsAddedEvent::class,
        MoreItemsAddedEvent::class,
        ItemDroppedEvent::class,
        ItemsEnhancedEvent::class,
        DropPickedUpEvent::class,
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
        RoomJoinedEvent::class,
        PlayerEnteredEvent::class,
        PlayerLeftEvent::class,
        PlayerUpdatedEvent::class,
        PlayerAwayUpdatedEvent::class,
        PlayerVitalsUpdatedEvent::class,
        StatsUpdatedEvent::class,
        PlayerStateUpdatedEvent::class,
        PlayerDataLoadedEvent::class,
        QuestLoadedEvent::class,
        ItemsTurnedInEvent::class,
        QuestAcceptedEvent::class,
        QuestCompletedEvent::class,
        QuestTurnInRefusedEvent::class,
        QuestMarkedEvent::class,
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
