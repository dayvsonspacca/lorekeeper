<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute;

final readonly class Vitals
{
    public function __construct(
        public Health $health,
        public Health $maxHealth,
        public Mana $mana,
        public Mana $maxMana,
    ) {}

    public function alive(): bool
    {
        return $this->health->value > 0;
    }

    public function withHealth(Health $health): self
    {
        return new self($health, $this->maxHealth, $this->mana, $this->maxMana);
    }

    public function withMana(Mana $mana): self
    {
        return new self($this->health, $this->maxHealth, $mana, $this->maxMana);
    }
}
