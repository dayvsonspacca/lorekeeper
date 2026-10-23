<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute;

use Lorekeeper\Core\Support\Gauge;

final readonly class Vitals
{
    /**
     * @param Gauge<Health> $health
     * @param Gauge<Mana> $mana
     */
    public function __construct(
        public Gauge $health,
        public Gauge $mana,
    ) {}

    public function alive(): bool
    {
        return !$this->health->empty();
    }
}
