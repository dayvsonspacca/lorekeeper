<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Asset\Linkage;

final readonly class Npc
{
    public function __construct(
        public Linkage $popup,
        public PopupButtons $buttons,
        public PopupText $text = new PopupText(null, null),
    ) {}
}
