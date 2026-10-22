<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Native\Tray;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class TrayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_menu_tells_which_bots_are_on(): void
    {
        Account::query()
            ->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))])
            ->update(['status' => BotStatus::Online]);
        Account::query()->create(['name' => 'Hero2', 'password' => bin2hex(random_bytes(8))]);

        static::assertSame(['● Hero · on', '○ Hero2 · off'], Tray::bots());
    }

    #[Test]
    public function the_menu_knows_when_a_bot_is_playing(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);

        static::assertFalse(Tray::playing());

        $account->update(['status' => BotStatus::Online]);

        static::assertTrue(Tray::playing());
    }

    #[Test]
    public function the_menu_has_nothing_playing_without_accounts(): void
    {
        static::assertSame([], Tray::bots());
        static::assertFalse(Tray::playing());
    }
}
