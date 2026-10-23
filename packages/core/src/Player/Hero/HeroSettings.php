<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Game\Setting\GameSetting;

final class HeroSettings
{
    /** @param list<GameSetting> $allowed */
    public function __construct(
        public private(set) array $allowed = [],
    ) {}

    public function allows(GameSetting $setting): bool
    {
        return in_array($setting, $this->allowed, strict: true);
    }

    public function allow(GameSetting $setting): void
    {
        if (!$this->allows($setting)) {
            $this->allowed[] = $setting;
        }
    }

    public function deny(GameSetting $setting): void
    {
        $this->allowed = array_values(array_filter(
            $this->allowed,
            static fn(GameSetting $allowed): bool => $allowed !== $setting,
        ));
    }
}
