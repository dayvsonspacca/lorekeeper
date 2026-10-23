<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute;

use Lorekeeper\Core\Support\Gauge;

final class Vitals
{
    /**
     * @param Gauge<Health> $health
     * @param Gauge<Mana> $mana
     */
    public function __construct(
        public private(set) Gauge $health,
        public private(set) Gauge $mana,
    ) {}

    public function alive(): bool
    {
        return !$this->health->empty();
    }

    public function hurt(Health $damage): void
    {
        $left = max(0, $this->health->current->value - $damage->value);
        $this->health = new Gauge(new Health($left), $this->health->maximum);
    }

    public function heal(Health $amount): void
    {
        $maximum = $this->health->maximum;
        $healed = min($maximum->value, $this->health->current->value + $amount->value);
        $this->health = new Gauge(new Health($healed), $maximum);
    }

    public function spend(Mana $cost): void
    {
        $left = max(0, $this->mana->current->value - $cost->value);
        $this->mana = new Gauge(new Mana($left), $this->mana->maximum);
    }

    public function regain(Mana $amount): void
    {
        $maximum = $this->mana->maximum;
        $regained = min($maximum->value, $this->mana->current->value + $amount->value);
        $this->mana = new Gauge(new Mana($regained), $maximum);
    }

    public function die(): void
    {
        $this->health = new Gauge(new Health(0), $this->health->maximum);
    }

    public function revive(): void
    {
        if ($this->alive()) {
            return;
        }

        $this->health = new Gauge($this->health->maximum, $this->health->maximum);
        $this->mana = new Gauge($this->mana->maximum, $this->mana->maximum);
    }

    /**
     * @param Gauge<Health> $health
     * @param Gauge<Mana> $mana
     */
    public function recount(Gauge $health, Gauge $mana): void
    {
        $this->health = $health;
        $this->mana = $mana;
    }
}
