<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\Weapon;

final readonly class WeaponDamage
{
    public function __construct(
        public DamageVariance $variance,
    ) {}
}
