<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\CreateBotRun;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ListBotRuns;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ViewBotRun;
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
    public function runs_can_be_stopped_and_started_again(): void
    {
        $run = $this->botRun();

        Livewire::test(ListBotRuns::class)->assertCanSeeTableRecords([$run])->callTableAction('stop', $run);
        static::assertSame(RunStatus::Stopping, $run->refresh()->status);

        $run->update(['status' => RunStatus::Stopped]);
        Livewire::test(ViewBotRun::class, ['record' => $run->id])->callAction('runAgain');

        static::assertSame(2, $run->account->runs()->count());
    }
}
