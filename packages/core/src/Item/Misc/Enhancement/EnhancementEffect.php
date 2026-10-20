<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Misc\Enhancement;

final readonly class EnhancementEffect
{
    public function __construct(
        public EffectName $name,
        public EffectDescription $description,
    ) {}
}
