<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Game\Setting\GameSetting;

final readonly class HeroSettings
{
    /** @param list<GameSetting> $allowed */
    public function __construct(
        public array $allowed = [],
    ) {}

    public function allows(GameSetting $setting): bool
    {
        return in_array($setting, $this->allowed, strict: true);
    }
}
