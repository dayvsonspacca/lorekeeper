<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Player\GameSetting;
use Lorekeeper\Core\Player\PlayerSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerSettingsTest extends TestCase
{
    #[Test]
    public function a_player_allows_only_the_settings_it_turned_on(): void
    {
        $settings = new PlayerSettings([GameSetting::Goto, GameSetting::Duels]);

        static::assertTrue($settings->allows(GameSetting::Goto));
        static::assertTrue($settings->allows(GameSetting::Duels));
        static::assertFalse($settings->allows(GameSetting::Whispers));
        static::assertFalse(new PlayerSettings()->allows(GameSetting::Goto));
    }
}
