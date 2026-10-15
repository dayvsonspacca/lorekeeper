<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;

final readonly class PopupButtons
{
    /**
     * @param list<QuestId> $quests
     * @param list<ShopId> $shops
     * @param list<MapName> $travels
     */
    public function __construct(
        public array $quests = [],
        public array $shops = [],
        public array $travels = [],
    ) {}

    public static function in(string $code): self
    {
        return new self(PopupQuests::in($code), PopupShops::in($code), PopupTravels::in($code));
    }

    public function isEmpty(): bool
    {
        return $this->quests === [] && $this->shops === [] && $this->travels === [];
    }
}
