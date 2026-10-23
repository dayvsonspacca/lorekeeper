<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use InvalidArgumentException;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Reward\RewardedEvent;

final readonly class WalletTracking
{
    /** @throws InvalidArgumentException */
    public static function follow(HeroWallet $wallet, Event $event, Identity $me): bool
    {
        return match (true) {
            $event instanceof PlayerDataLoadedEvent => self::recounted($wallet, PlayerDataTracking::mine($event, $me)),
            $event instanceof RewardedEvent && $event->gold->value > 0 => self::earned($wallet, $event),
            $event instanceof ItemsEnhancedEvent && $event->paid->value > 0 => self::paid($wallet, $event),
            default => false,
        };
    }

    private static function recounted(HeroWallet $wallet, ?PlayerData $data): bool
    {
        if ($data === null) {
            return false;
        }

        $wallet->recount($data->wallet->gold, $data->wallet->coins);

        return true;
    }

    private static function earned(HeroWallet $wallet, RewardedEvent $event): bool
    {
        $wallet->earn($event->gold);

        return true;
    }

    /** @throws InvalidArgumentException */
    private static function paid(HeroWallet $wallet, ItemsEnhancedEvent $event): bool
    {
        $wallet->pay($event->paid);

        return true;
    }
}
