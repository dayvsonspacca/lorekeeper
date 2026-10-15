<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcTitle;

final readonly class PopupText
{
    public function __construct(
        public ?NpcName $name,
        public ?NpcTitle $title,
    ) {}
}
