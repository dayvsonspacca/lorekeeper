<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero;

use Lorekeeper\Core\Player\Hero\HeroGameSetting;
use Lorekeeper\Core\Player\Hero\HeroSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HeroSettingsTest extends TestCase
{
    #[Test]
    public function a_player_allows_only_the_settings_it_turned_on(): void
    {
        $settings = new HeroSettings([HeroGameSetting::Goto, HeroGameSetting::Duels]);

        static::assertTrue($settings->allows(HeroGameSetting::Goto));
        static::assertTrue($settings->allows(HeroGameSetting::Duels));
        static::assertFalse($settings->allows(HeroGameSetting::Whispers));
        static::assertFalse(new HeroSettings()->allows(HeroGameSetting::Goto));
    }
}
