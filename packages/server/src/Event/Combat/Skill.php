<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Mana;
use Psl\DateTime\Duration;

final readonly class Skill
{
    /**
     * @param non-empty-string $ref
     * @param bool $hostile
     * @param bool $usable
     */
    public function __construct(
        public string $ref,
        public bool $hostile,
        public Mana $mana,
        public Duration $cooldown,
        public bool $usable,
    ) {}
}
