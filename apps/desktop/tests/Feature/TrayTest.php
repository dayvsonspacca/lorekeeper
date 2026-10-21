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
            ->create(['name' => 'made2903', 'password' => bin2hex(random_bytes(8))])
            ->update(['status' => BotStatus::Online]);
        Account::query()->create(['name' => 'lorekeeper03', 'password' => bin2hex(random_bytes(8))]);

        static::assertSame(['○ lorekeeper03 · off', '● made2903 · on'], Tray::bots());
    }
}
