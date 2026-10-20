<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Misc\Enhancement;

use Lorekeeper\Core\Item\ItemLevel;
use Psl\Option;

final readonly class Enhancement
{
    /** @param Option\Option<EnhancementEffect> $effect */
    public function __construct(
        public EnhancementType $type,
        public EnhancementRarity $rarity,
        public ItemLevel $level,
        public Option\Option $effect,
    ) {}
}
