<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Game\Level\Level;
use Override;

final readonly class PlayerLevel extends Level
{
    public function up(): self
    {
        return new self($this->value + 1);
    }

    #[Override]
    protected static function maximum(): int
    {
        return 100;
    }
}
