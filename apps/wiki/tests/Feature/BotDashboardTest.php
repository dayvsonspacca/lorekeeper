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
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\RelationManagers\LogsRelationManager;
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
    public function accounts_keep_their_password_unless_a_new_one_is_typed(): void
    {
        $this->actingAs($this->user());
        $first = bin2hex(random_bytes(8));

        Livewire::test(ManageAccounts::class)
            ->callAction(CreateAction::class, ['name' => 'Hero', 'password' => $first])
            ->assertHasNoActionErrors();
        $account = Account::query()->sole();

        Livewire::test(ManageAccounts::class)->callTableAction(EditAction::class, $account, [
            'name' => 'Hero',
            'password' => '',
            'server' => 'Twilly',
        ])->assertHasNoTableActionErrors();

        $account->refresh();
        static::assertSame($first, $account->password);
        static::assertSame('Twilly', $account->server);
    }

    #[Test]
    public function new_runs_wait_for_the_supervisor_and_can_be_stopped(): void
    {
        $this->actingAs($this->user());
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);

        Livewire::test(CreateBotRun::class)
            ->fillForm([
                'account_id' => $account->id,
                'script' => BotScript::StayInMap->value,
                'parameters' => ['map' => 'forest'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $run = BotRun::query()->sole();

        static::assertSame(RunStatus::Pending, $run->status);
        static::assertSame(['map' => 'forest'], $run->parameters);

        Livewire::test(ListBotRuns::class)->assertCanSeeTableRecords([$run])->callTableAction('stop', $run);

        static::assertSame(RunStatus::Stopping, $run->refresh()->status);
    }

    #[Test]
    public function a_run_shows_its_log(): void
    {
        $run = $this->botRun();
        $run->log('> Login < LoginSucceeded');

        Livewire::test(LogsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewBotRun::class])
            ->assertCanSeeTableRecords($run->logs()->get())
            ->assertSee('> Login < LoginSucceeded');
    }

    private function botRun(): BotRun
    {
        $this->actingAs($this->user());
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);

        return $account->runs()->create(['script' => BotScript::StayInMap, 'parameters' => ['map' => 'battleon']]);
    }

    private function user(): User
    {
        return User::query()->firstOrCreate(['email' => 'hero@example.com'], [
            'name' => 'Hero',
            'password' => bin2hex(random_bytes(8)),
        ]);
    }
}
