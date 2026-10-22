<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

final readonly class PlayerSettings
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
