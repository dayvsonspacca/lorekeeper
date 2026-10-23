<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

final readonly class HeroSettings
{
    /** @param list<HeroGameSetting> $allowed */
    public function __construct(
        public array $allowed = [],
    ) {}

    public function allows(HeroGameSetting $setting): bool
    {
        return in_array($setting, $this->allowed, strict: true);
    }
}
