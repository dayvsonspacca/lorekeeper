<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Native\Heartbeat;
use Lorekeeper\Apps\Desktop\Tauri\Poll;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

final class TauriShellTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = bin2hex(random_bytes(16));
        config(['desktop.shell' => 'tauri', 'desktop.shell_secret' => $this->secret]);
    }

    #[Test]
    public function the_shell_hears_which_bots_are_on(): void
    {
        Account::query()
            ->create(['name' => 'made2903', 'password' => bin2hex(random_bytes(8))])
            ->update(['status' => BotStatus::Online]);
        Account::query()->create(['name' => 'lorekeeper03', 'password' => bin2hex(random_bytes(8))]);

        $this
            ->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])
            ->assertOk()
            ->assertExactJson([
                'bots' => ['○ lorekeeper03 · off', '● made2903 · on'],
                'reload' => false,
            ]);
    }

    #[Test]
    public function the_shell_reloads_a_silent_window_once(): void
    {
        Heartbeat::beat();
        $this->travel(Heartbeat::PATIENCE_SECONDS + 1)->seconds();

        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertJsonPath('reload', true);
        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertJsonPath('reload', false);
    }

    #[Test]
    public function only_the_shell_may_poll(): void
    {
        $this->getJson('/shell')->assertForbidden();
        $this->getJson('/shell', [Poll::SECRET_HEADER => 'a-guess'])->assertForbidden();

        config(['desktop.shell_secret' => '']);
        $this->getJson('/shell', [Poll::SECRET_HEADER => ''])->assertForbidden();

        config(['desktop.shell' => null, 'desktop.shell_secret' => $this->secret]);
        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertForbidden();
    }

    #[Test]
    public function booting_marks_the_bots_the_last_session_left_playing_as_interrupted(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $account->update(['status' => BotStatus::Online, 'pid' => 4242]);

        $this->artisan('desktop:boot')->assertSuccessful();

        $account->refresh();
        static::assertSame(BotStatus::Failed, $account->status);
        static::assertSame('interrupted', $account->result);
        static::assertNull($account->pid);
    }
}
