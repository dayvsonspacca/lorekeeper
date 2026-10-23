<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Inventory;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Override;

final readonly class PickUpDropCommand implements Command
{
    public function __construct(
        private PlayerSpawnId $player,
        private ItemId $item,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%getDrop%{$this->player}%{$this->item->value}%";
    }
}
