<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\CreateBotRun;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ListBotRuns;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Wiki\Livewire\RunEvents;
use Lorekeeper\Apps\Wiki\Livewire\RunLog;
use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\User;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BotDashboardTest extends TestCase
{
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
    public function the_run_form_asks_for_what_the_chosen_script_needs(): void
    {
        $this->actingAs($this->user());
        $account = $this->account();

        Livewire::test(CreateBotRun::class)
            ->assertSet('data.parameters', ['map' => null])
            ->set('data', [
                'account_id' => $account->id,
                'script' => BotScript::StayInMap->value,
                'parameters' => ['map' => null],
            ])
            ->set('data.parameters.map', 'battleon')
            ->call('create')
            ->assertHasNoFormErrors();
        $account->runs()->delete();

        Livewire::test(ManageAccounts::class)
            ->mountTableAction('startRun', $account)
            ->assertTableActionDataSet(['parameters' => ['map' => null]]);

        Livewire::test(CreateBotRun::class)
            ->assertDontSee('Parameters')
            ->fillForm(['account_id' => $account->id, 'script' => BotScript::StayInMap->value])
            ->assertSee('Parameters')
            ->fillForm(['parameters' => ['map' => 'forest']])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect("/admin/bot-runs/{$account->runs()->sole()->id}");

        $run = BotRun::query()->sole();
        static::assertSame(RunStatus::Pending, $run->status);
        static::assertSame(['map' => 'forest'], $run->parameters);
    }

    #[Test]
    public function accounts_start_runs_and_runs_can_be_stopped_and_run_again(): void
    {
        $this->actingAs($this->user());
        $account = $this->account();

        Livewire::test(ManageAccounts::class)
            ->callTableAction('startRun', $account, [
                'script' => BotScript::StayInMap->value,
                'parameters' => ['map' => 'battleon'],
            ])
            ->assertHasNoTableActionErrors();
        $run = BotRun::query()->sole();

        Livewire::test(ListBotRuns::class)->assertCanSeeTableRecords([$run])->callTableAction('stop', $run);
        static::assertSame(RunStatus::Stopping, $run->refresh()->status);

        $run->update(['status' => RunStatus::Stopped]);
        Livewire::test(ViewBotRun::class, ['record' => $run->id])->callAction('runAgain');

        static::assertSame(2, $account->runs()->count());
        static::assertSame(['map' => 'battleon'], $account->runs()->latest('id')->firstOrFail()->parameters);
    }

    #[Test]
    public function a_run_shows_its_log_like_a_terminal(): void
    {
        $run = $this->botRun();
        $run->update(['status' => RunStatus::Failed, 'started_at' => now()->subMinutes(2), 'finished_at' => now()]);
        $run->log('> Login < LoginSucceeded');
        $run->log('> LoadShop … timed out after 15s');
        $run->log('= failed: no open server');

        Livewire::test(RunLog::class, ['run' => $run])
            ->assertSeeInOrder([
                '> Login < LoginSucceeded',
                '> LoadShop … timed out after 15s',
                '= failed: no open server',
            ])
            ->assertSee('Failed')
            ->assertSee('2m 00s')
            ->assertDontSeeHtml('wire:poll');
    }

    #[Test]
    public function a_run_shows_the_events_it_heard_and_filters_them(): void
    {
        $run = $this->botRun();
        $run->events()->create(['name' => 'ItemEquipped', 'summary' => 'Socket 7 equipped item 31660']);
        $run->events()->create([
            'name' => 'Unrecognized',
            'summary' => 'uotls',
            'raw' => '%xt%uotls%-1%hero%afk:true%',
        ]);

        Livewire::test(RunEvents::class, ['run' => $run])
            ->assertSee('All · 2')
            ->assertSee('Socket 7 equipped item 31660')
            ->assertSee('%xt%uotls%-1%hero%afk:true%')
            ->call('show', 'Unrecognized')
            ->assertDontSee('Socket 7 equipped item 31660')
            ->call('show', 'Unrecognized')
            ->assertSee('Socket 7 equipped item 31660');
    }

    #[Test]
    public function the_log_tells_the_page_when_the_run_changes(): void
    {
        $run = $this->botRun();
        $log = Livewire::test(RunLog::class, ['run' => $run])->assertSeeHtml('wire:poll.2s="refreshRun"');

        $log->call('refreshRun')->assertNotDispatched('run-changed');

        $run->update(['status' => RunStatus::Running]);
        $log->call('refreshRun')->assertDispatched('run-changed')->assertSee('Running');

        $run->update(['started_at' => now()]);
        $log->call('refreshRun')->assertDispatched('run-changed');

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->assertActionVisible('stop')
            ->tap(static fn() => $run->update(['status' => RunStatus::Stopped, 'finished_at' => now()]))
            ->dispatch('run-changed')
            ->assertActionHidden('stop')
            ->assertActionVisible('runAgain');
    }

    private function botRun(): BotRun
    {
        $this->actingAs($this->user());

        return $this
            ->account()
            ->runs()
            ->create(['script' => BotScript::StayInMap, 'parameters' => ['map' => 'battleon']]);
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }

    private function user(): User
    {
        return User::query()->firstOrCreate(['email' => 'hero@example.com'], [
            'name' => 'Hero',
            'password' => bin2hex(random_bytes(8)),
        ]);
    }
}
