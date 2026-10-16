<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\CreateBotRun;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ListBotRuns;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Wiki\Livewire\RunTasks;
use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BotDashboardTest extends TestCase
{
    use CreatesBots;
    use RefreshDatabase;

    #[Test]
    public function every_page_opens(): void
    {
        $run = $this->botRun();

        foreach ([
            '/admin/accounts',
            '/admin/bot-runs',
            '/admin/bot-runs/create',
            "/admin/bot-runs/{$run->id}",
        ] as $page) {
            $this->get($page)->assertOk();
        }
    }

    #[Test]
    public function accounts_pick_a_server_and_keep_their_password_unless_a_new_one_is_typed(): void
    {
        $this->actingAs($this->user());
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
    }

    #[Test]
    public function runs_start_from_an_account_or_the_runs_page_without_a_script(): void
    {
        $this->actingAs($this->user());
        $account = $this->account();

        Livewire::test(ManageAccounts::class)->callTableAction('startRun', $account)->assertHasNoTableActionErrors();
        $fromAccount = $account->runs()->sole();
        static::assertSame(RunStatus::Pending, $fromAccount->status);

        $fromAccount->update(['status' => RunStatus::Stopped]);
        Livewire::test(CreateBotRun::class)
            ->fillForm(['account_id' => $account->id])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/bot-runs/' . $account->runs()->latest('id')->firstOrFail()->id);
    }

    #[Test]
    public function tasks_ask_for_what_their_script_needs(): void
    {
        $run = $this->botRun();

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->mountAction('addTask')
            ->assertActionDataSet(['parameters' => ['map' => null, 'quests' => null, 'shops' => null]])
            ->setActionData(['script' => BotScript::AskQuests->value])
            ->setActionData(['parameters' => ['map' => 'battleon', 'quests' => ['3', 'x']]])
            ->callMountedAction()
            ->assertHasActionErrors(['parameters.quests.1']);

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->callAction('addTask', ['script' => BotScript::GoToMap->value, 'parameters' => ['map' => 'not a map!']])
            ->assertHasActionErrors(['parameters.map']);

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->callAction('addTask', ['script' => BotScript::GoToMap->value, 'parameters' => ['map' => 'secretmap-7']])
            ->assertHasNoActionErrors();

        $task = $run->tasks()->sole();
        static::assertSame(TaskStatus::Pending, $task->status);
        static::assertSame('secretmap-7', $task->parameters['map']);
    }

    #[Test]
    public function tasks_can_be_cancelled_while_waiting_or_playing(): void
    {
        $run = $this->botRun();
        $waiting = $run->tasks()->create(['script' => BotScript::GoToMap, 'parameters' => ['map' => 'forest']]);
        $playing = $run->tasks()->create([
            'script' => BotScript::GoToMap,
            'parameters' => ['map' => 'yulgar'],
            'status' => TaskStatus::Running,
        ]);

        Livewire::test(RunTasks::class, ['run' => $run])
            ->assertSee('Go to map')
            ->assertSee('yulgar')
            ->call('cancel', $waiting->id)
            ->call('cancel', $playing->id);

        static::assertSame(TaskStatus::Cancelled, $waiting->refresh()->status);
        static::assertSame(TaskStatus::Cancelling, $playing->refresh()->status);
    }

    #[Test]
    public function runs_can_be_stopped_and_started_again(): void
    {
        $run = $this->botRun();

        Livewire::test(ListBotRuns::class)->assertCanSeeTableRecords([$run])->callTableAction('stop', $run);
        static::assertSame(RunStatus::Stopping, $run->refresh()->status);

        $run->update(['status' => RunStatus::Stopped]);
        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->assertActionHidden('addTask')
            ->callAction('runAgain');

        static::assertSame(2, $run->account->runs()->count());
    }
}
