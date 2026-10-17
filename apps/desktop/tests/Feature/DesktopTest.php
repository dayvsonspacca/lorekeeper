<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Desktop\Livewire\RunProgress;
use Lorekeeper\Apps\Desktop\Livewire\RunTasks;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class DesktopTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_page_opens_without_logging_in(): void
    {
        $run = $this->account()->runs()->create();

        $this->get('/')->assertRedirect();
        foreach (['/accounts', '/bot-runs', '/bot-runs/create', "/bot-runs/{$run->id}"] as $page) {
            $this->get($page)->assertOk();
        }
    }

    #[Test]
    public function accounts_are_added_and_keep_their_password_unless_a_new_one_is_typed(): void
    {
        $first = bin2hex(random_bytes(8));

        Livewire::test(ManageAccounts::class)
            ->callAction(CreateAction::class, ['name' => 'Hero', 'password' => $first, 'server' => 'Twilly'])
            ->assertHasNoActionErrors();
        $account = Account::query()->sole();

        Livewire::test(ManageAccounts::class)->callTableAction(EditAction::class, $account, [
            'name' => 'Hero',
            'password' => '',
            'server' => 'Espada',
        ])->assertHasNoTableActionErrors();

        $account->refresh();
        static::assertSame($first, $account->password);
        static::assertSame('Espada', $account->server);
        static::assertNotSame($first, $account->getRawOriginal('password'));
    }

    #[Test]
    public function starting_a_bot_launches_its_process_once_at_a_time(): void
    {
        $account = $this->account();

        Livewire::test(ManageAccounts::class)->callTableAction('startRun', $account)->assertHasNoTableActionErrors();
        $run = $account->runs()->sole();

        static::assertSame([$run->id], $this->launcher->launched);
        static::assertSame(RunStatus::Pending, $run->status);
        Livewire::test(ManageAccounts::class)
            ->assertTableActionHidden('startRun', $account)
            ->assertTableActionVisible('openRun', $account);
    }

    #[Test]
    public function tasks_are_queued_and_show_how_far_along_they_are(): void
    {
        $run = $this->account()->runs()->create();
        $run->update(['status' => RunStatus::Running]);

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->callAction('addTask', [
                'script' => BotScript::OpenShops->value,
                'parameters' => [
                    'map' => 'battleon',
                    'shops' => ['31', '609', '1', '2'],
                ],
            ])
            ->assertHasNoActionErrors();
        $task = $run->tasks()->sole();
        $task->update(['status' => TaskStatus::Running, 'progress_done' => 1, 'progress_total' => 4]);

        static::assertSame(25, $task->refresh()->percent());
        Livewire::test(RunTasks::class, ['run' => $run])->assertSee('1/4 · 25%');
        $this->get('/bot-runs')->assertSee('Open shops')->assertSee('25%');
    }

    #[Test]
    public function the_run_page_shows_how_a_level_up_is_going(): void
    {
        $account = $this->account();
        $account->update(['level' => 6]);
        $run = $account->runs()->create();
        $run->update(['status' => RunStatus::Running]);
        $run->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 10],
            'status' => TaskStatus::Running,
            'started_at' => now()->subMinutes(20),
            'level_from' => 4,
            'kills' => 60,
            'experience' => 600,
            'gold' => 180,
            'deaths' => 1,
            'progress_done' => 2,
            'progress_total' => 6,
        ]);

        Livewire::test(RunProgress::class, ['run' => $run])
            ->assertSee('Monsters killed')
            ->assertSee('3 per minute')
            ->assertSee('10 min per level')
            ->assertSee('4 levels')
            ->assertSee('33%');
        $this->get('/accounts')->assertSee('Level');
    }

    #[Test]
    public function a_bot_can_be_told_to_level_up(): void
    {
        $run = $this->account()->runs()->create();
        $run->update(['status' => RunStatus::Running]);

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->callAction('addTask', ['script' => BotScript::LevelUp->value, 'parameters' => ['level' => '1']])
            ->assertHasActionErrors(['parameters.level']);
        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->callAction('addTask', ['script' => BotScript::LevelUp->value, 'parameters' => ['level' => '30']])
            ->assertHasNoActionErrors();

        $task = $run->tasks()->sole();
        static::assertSame(BotScript::LevelUp, $task->script);
        static::assertSame('Level up · to level 30', $task->title());
    }

    #[Test]
    public function stopping_asks_the_bot_to_log_out_or_ends_a_run_whose_bot_is_gone(): void
    {
        $account = $this->account();
        $alive = $account->runs()->create();
        $alive->update(['status' => RunStatus::Running]);

        Livewire::test(ViewBotRun::class, ['record' => $alive->id])->callAction('stop');
        static::assertSame(RunStatus::Stopping, $alive->refresh()->status);

        $gone = $account->runs()->create();
        $gone->update(['status' => RunStatus::Running]);
        $gone->tasks()->create(['script' => BotScript::GoToMap, 'parameters' => ['map' => 'forest']]);
        unset($this->launcher->alive[$gone->id]);

        Livewire::test(ViewBotRun::class, ['record' => $gone->id])->callAction('stop');
        static::assertSame(RunStatus::Stopped, $gone->refresh()->status);
        static::assertSame(TaskStatus::Cancelled, $gone->tasks()->sole()->status);
    }

    #[Test]
    public function runs_left_active_by_a_closed_app_end_as_interrupted(): void
    {
        $account = $this->account();
        $running = $account->runs()->create();
        $running->update(['status' => RunStatus::Running]);
        $running->tasks()->create(['script' => BotScript::GoToMap, 'parameters' => ['map' => 'forest']]);
        $done = $account->runs()->create();
        $done->update(['status' => RunStatus::Stopped]);

        BotRun::interrupted();

        static::assertSame(RunStatus::Failed, $running->refresh()->status);
        static::assertSame('interrupted', $running->result);
        static::assertSame(TaskStatus::Cancelled, $running->tasks()->sole()->status);
        static::assertSame(RunStatus::Stopped, $done->refresh()->status);
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }
}
