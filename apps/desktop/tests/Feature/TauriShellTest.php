<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\SilentNotifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\TaskNotice;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Native\Heartbeat;
use Lorekeeper\Apps\Desktop\Tauri\Poll;
use Lorekeeper\Apps\Desktop\Tauri\TauriNotifier;
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
            ->runs()
            ->create()
            ->update(['status' => RunStatus::Running]);
        Account::query()->create(['name' => 'lorekeeper03', 'password' => bin2hex(random_bytes(8))]);

        $this
            ->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])
            ->assertOk()
            ->assertExactJson([
                'bots' => ['○ lorekeeper03 · off', '● made2903 · on'],
                'notices' => [],
                'reload' => false,
            ]);
    }

    #[Test]
    public function the_shell_shows_each_notice_once(): void
    {
        $notifier = app(Notifier::class);
        static::assertInstanceOf(TauriNotifier::class, $notifier);

        $notifier->show(new TaskNotice('Hero · Level up', 'Reached level 6'));
        $notifier->show(new TaskNotice('Hero · Level up', 'Failed: Could not join icestormarena'));

        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertJsonPath('notices', [
            ['title' => 'Hero · Level up', 'message' => 'Reached level 6'],
            ['title' => 'Hero · Level up', 'message' => 'Failed: Could not join icestormarena'],
        ]);
        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertJsonPath('notices', []);
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
        $this->getJson('/shell')->assertNotFound();
        $this->getJson('/shell', [Poll::SECRET_HEADER => 'a-guess'])->assertNotFound();

        config(['desktop.shell_secret' => '']);
        $this->getJson('/shell', [Poll::SECRET_HEADER => ''])->assertNotFound();

        config(['desktop.shell' => null, 'desktop.shell_secret' => $this->secret]);
        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertNotFound();
        static::assertInstanceOf(SilentNotifier::class, app(Notifier::class));
    }

    #[Test]
    public function booting_closes_the_runs_the_last_session_left_open(): void
    {
        $run = Account::query()
            ->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))])
            ->runs()
            ->create();
        $run->update(['status' => RunStatus::Running, 'pid' => 4242]);

        $this->artisan('desktop:boot')->assertSuccessful();

        $run = BotRun::query()->findOrFail($run->id);
        static::assertSame(RunStatus::Failed, $run->status);
        static::assertSame('interrupted', $run->result);
    }
}
