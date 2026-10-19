<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Reward\RewardedEvent;

final readonly class WalletTracking
{
    public static function follow(Wallet $wallet, Event $event, Identity $me): Wallet
    {
        return match (true) {
            $event instanceof PlayerDataLoadedEvent => PlayerDataTracking::mine($event, $me)->wallet ?? $wallet,
            $event instanceof RewardedEvent => self::earned($wallet, $event->gold),
            default => $wallet,
        };
    }

    private static function earned(Wallet $wallet, Gold $gold): Wallet
    {
        return $gold->value === 0 ? $wallet : new Wallet(new Gold($wallet->gold->value + $gold->value), $wallet->coins);
    }
}
