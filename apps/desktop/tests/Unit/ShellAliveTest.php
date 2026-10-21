<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Tauri\Shell;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ShellAliveTest extends TestCase
{
    private const int NO_SUCH_PROCESS = 999_999_999;

    #[Test]
    public function the_bots_keep_playing_while_the_shell_runs(): void
    {
        config(['desktop.shell' => 'tauri', 'desktop.shell_pid' => (string) getmypid()]);

        static::assertTrue(Shell::alive());
    }

    #[Test]
    public function the_bots_learn_when_the_shell_is_gone(): void
    {
        config(['desktop.shell' => 'tauri', 'desktop.shell_pid' => (string) self::NO_SUCH_PROCESS]);

        static::assertFalse(Shell::alive());
    }

    #[Test]
    public function without_a_shell_to_follow_the_bots_keep_playing(): void
    {
        config(['desktop.shell' => null, 'desktop.shell_pid' => (string) self::NO_SUCH_PROCESS]);
        static::assertTrue(Shell::alive());

        config(['desktop.shell' => 'tauri', 'desktop.shell_pid' => null]);
        static::assertTrue(Shell::alive());
    }
}
