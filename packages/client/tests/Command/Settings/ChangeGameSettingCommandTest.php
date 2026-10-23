<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Settings;

use Lorekeeper\Client\Command\Settings\ChangeGameSettingCommand;
use Lorekeeper\Core\Game\Setting\GameSetting;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChangeGameSettingCommandTest extends TestCase
{
    #[Test]
    public function it_blocks_each_setting_the_game_lets_a_player_turn_off(): void
    {
        static::assertSame(
            [
                '%xt%zm%cmd%1%uopref%bGoto%false%',
                '%xt%zm%cmd%1%uopref%bWhisper%false%',
                '%xt%zm%cmd%1%uopref%bFriend%false%',
                '%xt%zm%cmd%1%uopref%bParty%false%',
                '%xt%zm%cmd%1%uopref%bGuild%false%',
                '%xt%zm%cmd%1%uopref%bDuel%false%',
            ],
            array_map(static fn(GameSetting $setting): string => new ChangeGameSettingCommand(
                $setting,
                allowed: false,
            )->toMessage(), GameSetting::cases()),
        );
    }

    #[Test]
    public function it_allows_a_setting_again(): void
    {
        static::assertSame(
            '%xt%zm%cmd%1%uopref%bGoto%true%',
            new ChangeGameSettingCommand(GameSetting::Goto, allowed: true)->toMessage(),
        );
    }
}
