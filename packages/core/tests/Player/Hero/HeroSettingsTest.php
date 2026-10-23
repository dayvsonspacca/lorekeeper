<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero;

use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Core\Player\Hero\HeroSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HeroSettingsTest extends TestCase
{
    #[Test]
    public function a_player_allows_only_the_settings_it_turned_on(): void
    {
        $settings = new HeroSettings([GameSetting::Goto, GameSetting::Duels]);

        static::assertTrue($settings->allows(GameSetting::Goto));
        static::assertTrue($settings->allows(GameSetting::Duels));
        static::assertFalse($settings->allows(GameSetting::Whispers));
        static::assertFalse(new HeroSettings()->allows(GameSetting::Goto));
    }
}
