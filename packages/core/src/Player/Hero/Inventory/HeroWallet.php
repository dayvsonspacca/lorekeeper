<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Currency;
use Lorekeeper\Core\World\Currency\Gold;

final class HeroWallet
{
    public function __construct(
        public private(set) Gold $gold = new Gold(0),
        public private(set) AdventureCoins $coins = new AdventureCoins(0),
    ) {}

    public function earn(Currency $amount): void
    {
        $this->hold($amount, $this->held($amount) + $amount->value);
    }

    /** @throws InvalidArgumentException */
    public function pay(Currency $amount): void
    {
        $held = $this->held($amount);
        if ($amount->value > $held) {
            throw new InvalidArgumentException(
                "Cannot pay {$amount}{$amount->symbol()} holding only {$held}{$amount->symbol()}.",
            );
        }

        $this->hold($amount, $held - $amount->value);
    }

    public function affords(ItemPrice $price): bool
    {
        return $this->held($price->amount) >= $price->amount->value;
    }

    public function recount(Gold $gold, AdventureCoins $coins): void
    {
        $this->gold = $gold;
        $this->coins = $coins;
    }

    private function held(Currency $currency): int
    {
        return $currency instanceof Gold ? $this->gold->value : $this->coins->value;
    }

    private function hold(Currency $currency, int $total): void
    {
        if ($currency instanceof Gold) {
            $this->gold = new Gold($total);

            return;
        }

        $this->coins = new AdventureCoins($total);
    }
}
