<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\Scene\Backpack;
use Lorekeeper\Apps\Desktop\Bots\Scene\Feed;
use Lorekeeper\Apps\Desktop\Bots\Scene\FeedKind;
use Lorekeeper\Apps\Desktop\Bots\Scene\Pulse;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Filament\Pages\Bots;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Desktop\Livewire\KeepAlive;
use Lorekeeper\Apps\Desktop\Livewire\RunBoard;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/** @mago-expect lint:too-many-methods */
final class DesktopTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_page_opens_without_logging_in(): void
    {
        $run = $this->account()->runs()->create();

        foreach (['/', '/accounts', '/bot-runs', '/bot-runs/create', "/bot-runs/{$run->id}"] as $page) {
            $this->get($page)->assertOk()->assertSeeLivewire(KeepAlive::class);
        }
    }

    #[Test]
    public function the_home_shows_a_row_per_character_to_start_stop_and_open_its_bot(): void
    {
        $idle = $this->account();
        $busy = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8)), 'level' => 6]);
        $run = $busy->runs()->create();
        $run->update(['status' => RunStatus::Running, 'map' => 'icestormarena']);
        $run->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 10],
            'status' => TaskStatus::Running,
            'started_at' => now()->subMinutes(5),
            'kills' => 12,
            'progress_done' => 1,
            'progress_total' => 4,
        ]);

        Livewire::test(Bots::class)
            ->assertSeeInOrder([
                'Busy',
                'Entering',
                'icestormarena',
                'Level up · to level 10',
                '25%',
                'Stop',
                'Entering the game',
                '12 kills',
                'Queue',
                'Watch the run',
                'Hero',
                'Never ran',
                'Start',
            ])
            ->call('start', $idle->id)
            ->callAction('stop', arguments: ['run' => $run->id]);

        static::assertSame(RunStatus::Pending, $idle->activeRun()?->status);
        static::assertSame(RunStatus::Stopping, $run->refresh()->status);
    }

    #[Test]
    public function the_home_shows_what_a_bot_in_the_game_sees(): void
    {
        $this->botInTheGame();

        Livewire::test(Bots::class)->assertSeeInOrder([
            'Online',
            '1 of 1',
            'Played today',
            'Tasks done today',
            'Gold today',
            '80',
            'Failed in 24h',
            'Hero',
            'Mage',
            'Fighting',
            'icestormarena',
            'Level up · to level 40',
            '1,840',
            '2,300',
            'Frost Spirit',
            '2 kills',
            '1,200 XP',
            'XP / hour',
            'Watch the run',
        ]);
    }

    #[Test]
    public function the_run_shows_its_bot_task_log_and_map_on_one_board(): void
    {
        $run = $this->botInTheGame();
        $run->log('Joined icestormarena');

        Livewire::test(RunBoard::class, ['run' => $run])
            ->assertSeeInOrder([
                'Fighting',
                'Level 34 · rank 7',
                'Mage',
                'icestormarena',
                '1 player',
                '1,840',
                '2,300',
                '21,000',
                '60,000',
                '123,456',
                'Now',
                'Level up · to level 40',
                'Pace',
                'XP per hour',
                '1,200',
                'to level 40',
                'Tasks',
                'Log',
                'Joined icestormarena',
                'Map',
                'r3a',
                '1/1',
                'r4',
                '1/1',
                'r3a · fighting here',
                'Frost Spirit',
                'Lv 33',
            ])
            ->assertDontSee('Chronicle');
    }

    #[Test]
    public function the_inventory_and_skills_wait_behind_their_tabs(): void
    {
        $board = Livewire::test(RunBoard::class, ['run' => $this->botInTheGame()]);

        $board->assertDontSee('Held for now');
        $board
            ->call('open', 'inventory')
            ->assertSeeInOrder(['Held for now', 'Metal Ore', 'Kept', 'Healer', 'Crypto Token'])
            ->assertDontSee('Frost Spirit');
        static::assertStringContainsString('×4', $board->html());
        $board
            ->call('open', 'skills')
            ->assertSeeInOrder(['+15', 'Rank 7', '23% to rank 8', '90,000', 'class points'])
            ->assertDontSee('Held for now');
        static::assertStringContainsString('×3', $board->html());
        $board->call('open', 'anything')->assertSee('Frost Spirit');
    }

    #[Test]
    public function the_map_shows_one_cell_at_a_time_and_goes_back_to_the_fight(): void
    {
        $scene = Livewire::test(RunBoard::class, ['run' => $this->botInTheGame()]);

        $scene->assertSee('Frost Spirit')->assertDontSee('Snow Golem');

        $scene
            ->call('show', 'r4')
            ->assertSee('Snow Golem')
            ->assertDontSee('Lv 33')
            ->assertSee('back to the fight in r3a');

        $scene->call('follow')->assertSee('r3a · fighting here')->assertDontSee('Snow Golem');
    }

    #[Test]
    public function a_bot_resumes_the_tasks_its_last_run_left_unfinished(): void
    {
        $account = $this->account();
        $last = $account->runs()->create();
        $last->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 5],
            'status' => TaskStatus::Done,
        ]);
        $last->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 50],
            'status' => TaskStatus::Running,
        ]);
        $last->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 60]]);
        BotRun::interrupted();

        Livewire::test(Bots::class)
            ->assertSeeInOrder(['Hero', 'Left off', 'Level up · to level 50', '+1 more', 'Resume', 'Start'])
            ->call('resume', $account->id)
            ->call('resume', $account->id);

        $run = $account->activeRun();
        static::assertNotNull($run);
        static::assertNotSame($last->id, $run->id);
        static::assertSame([$last->id, $run->id], $this->launcher->launched);
        static::assertSame(
            [[['level' => 50], 'pending'], [['level' => 60], 'pending']],
            $run
                ->tasks()
                ->orderBy('id')
                ->get()
                ->map(static fn(BotTask $task): array => [$task->parameters, $task->status->value])
                ->all(),
        );
    }

    #[Test]
    public function a_task_is_cancelled_after_confirming(): void
    {
        $run = $this->account()->runs()->create();
        $run->update(['status' => RunStatus::Running]);
        $pending = $run->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 10]]);

        Livewire::test(RunBoard::class, ['run' => $run])
            ->mountAction('cancel', ['task' => $pending->id])
            ->assertActionMounted('cancel')
            ->callMountedAction();

        static::assertSame(TaskStatus::Cancelled, $pending->refresh()->status);
    }

    #[Test]
    public function a_row_opens_to_show_its_queue_and_takes_new_tasks(): void
    {
        $account = $this->account();
        $run = $account->runs()->create();
        $run->update(['status' => RunStatus::Running]);
        $run->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 40]]);
        $run->log('Joined icestormarena');

        Livewire::test(Bots::class)
            ->assertSet('expanded', $account->id)
            ->assertSee(['Level up · to level 40', 'Joined icestormarena'])
            ->callAction(
                'addTask',
                data: [
                    'script' => BotScript::CryptoToken->value,
                ],
                arguments: ['run' => $run->id],
            )
            ->assertHasNoActionErrors()
            ->assertSee('Crypto Token daily · for today')
            ->call('toggle', $account->id)
            ->assertSet('expanded', null)
            ->assertDontSee('Joined icestormarena');

        static::assertSame(2, $run->tasks()->count());
    }

    #[Test]
    public function every_idle_bot_starts_at_once(): void
    {
        $idle = $this->account();
        $busy = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8))]);
        $run = $busy->runs()->create();

        Livewire::test(Bots::class)->callAction('startIdle');

        static::assertNotNull($idle->activeRun());
        static::assertSame($run->id, $busy->activeRun()?->id);
        static::assertSame(1, $busy->runs()->count());
    }

    #[Test]
    public function characters_can_be_added_from_the_home(): void
    {
        Livewire::test(Bots::class)
            ->callAction('addAccount', [
                'name' => 'Fresh',
                'password' => bin2hex(random_bytes(8)),
                'server' => 'Twilly',
            ])
            ->assertHasNoActionErrors();

        static::assertSame(
            'Twilly',
            Account::query()
                ->where('name', 'Fresh')
                ->sole()
                ->server,
        );
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
                'script' => BotScript::LevelUp->value,
                'parameters' => ['level' => '8'],
            ])
            ->assertHasNoActionErrors();
        $task = $run->tasks()->sole();
        $task->update(['status' => TaskStatus::Running, 'progress_done' => 1, 'progress_total' => 4]);

        static::assertSame(25, $task->refresh()->percent());
        Livewire::test(RunBoard::class, ['run' => $run])->assertSee('1/4 · 25%');
        $this->get('/bot-runs')->assertSee('Level up')->assertSee('25%');
    }

    #[Test]
    public function a_level_up_task_carries_its_own_report(): void
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
            'level_to' => 6,
            'kills' => 60,
            'experience' => 600,
            'gold' => 180,
            'deaths' => 1,
            'progress_done' => 2,
            'progress_total' => 6,
        ]);

        $run->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 20]]);

        Livewire::test(RunBoard::class, ['run' => $run])
            ->assertSee('Report')
            ->assertSeeInOrder(['to level 10', 'Monsters killed', 'to level 20'])
            ->assertSee('3 per minute')
            ->assertSee('10 min per level')
            ->assertSee('4 levels')
            ->assertSee('33%');
        static::assertSame(1, substr_count(
            Livewire::test(RunBoard::class, ['run' => $run])->html(),
            needle: 'Monsters killed',
        ));
        $this->get("/bot-runs/{$run->id}")->assertDontSee('Progress shows up');
        $this->get('/accounts')->assertSee('Level');
    }

    #[Test]
    public function a_bot_can_be_told_to_play_the_crypto_token_daily(): void
    {
        $run = $this->account()->runs()->create();
        $run->update(['status' => RunStatus::Running]);

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->callAction('addTask', ['script' => BotScript::CryptoToken->value])
            ->assertHasNoActionErrors();

        $task = $run->tasks()->sole();
        static::assertSame(BotScript::CryptoToken, $task->script);
        static::assertSame('Crypto Token daily · for today', $task->title());
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
        $gone->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 10]]);
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
        $running->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 10]]);
        $done = $account->runs()->create();
        $done->update(['status' => RunStatus::Stopped]);

        BotRun::interrupted();

        static::assertSame(RunStatus::Failed, $running->refresh()->status);
        static::assertSame('interrupted', $running->result);
        static::assertSame(TaskStatus::Cancelled, $running->tasks()->sole()->status);
        static::assertSame(RunStatus::Stopped, $done->refresh()->status);
    }

    private function botInTheGame(): BotRun
    {
        $account = $this->account();
        $run = $account->runs()->create();
        $now = now()->getTimestamp();
        $run->update([
            'status' => RunStatus::Running,
            'scene' => new Scene(
                self::hero(),
                [
                    [
                        'id' => 3,
                        'name' => 'Frost Spirit',
                        'level' => 33,
                        'race' => 'elemental',
                        'cell' => 'r3a',
                        'health' => 900,
                        'maxHealth' => 3000,
                        'state' => 'fighting',
                        'struck' => $now,
                    ],
                    [
                        'id' => 7,
                        'name' => 'Snow Golem',
                        'level' => 35,
                        'race' => 'elemental',
                        'cell' => 'r4',
                        'health' => 4000,
                        'maxHealth' => 4000,
                        'state' => 'idle',
                        'struck' => null,
                    ],
                ],
                ['Artix'],
                new Pulse()
                    ->rewarded($now - 120, experience: 500)
                    ->rewarded($now - 60, experience: 700),
                new Feed()->add($now - 30, FeedKind::Defeated, 'Defeated Frost Spirit', '+700 XP · +40 gold'),
                $now,
                self::backpack(),
            )->toArray(),
        ]);

        $run->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 40],
            'status' => TaskStatus::Running,
            'started_at' => now()->subMinutes(3),
            'level_from' => 34,
            'kills' => 2,
            'experience' => 1200,
            'gold' => 80,
        ]);

        return $run;
    }

    /** @return array<string, mixed> */
    private static function hero(): array
    {
        return [
            'name' => 'Hero',
            'level' => 34,
            'experience' => 21_000,
            'maxExperience' => 60_000,
            'gold' => 123_456,
            'member' => true,
            'health' => 1840,
            'maxHealth' => 2300,
            'mana' => 61,
            'maxMana' => 100,
            'class' => 'Mage',
            'category' => 'caster',
            'rank' => 7,
            'points' => 90_000,
            'rankFrom' => 78_400,
            'rankTo' => 129_600,
            'state' => 'fighting',
            'away' => false,
            'map' => 'icestormarena',
            'items' => 41,
            'skills' => [
                [
                    'slot' => 'AutoAttack',
                    'cost' => 0,
                    'cooldown' => 2.0,
                    'targets' => 1,
                    'target' => 'enemy',
                    'unlocked' => true,
                ],
                [
                    'slot' => 'First',
                    'cost' => 20,
                    'cooldown' => 6.0,
                    'targets' => 3,
                    'target' => 'enemy',
                    'unlocked' => true,
                ],
                [
                    'slot' => 'Second',
                    'cost' => 0,
                    'restores' => 15,
                    'cooldown' => 7.0,
                    'targets' => 3,
                    'target' => 'enemy',
                    'unlocked' => true,
                ],
            ],
        ];
    }

    private static function backpack(): Backpack
    {
        return new Backpack([
            [
                'id' => 15_651,
                'name' => 'Healer',
                'kind' => 'class',
                'type' => 'Class',
                'quantity' => 1,
                'member' => false,
            ],
            [
                'id' => 42_657,
                'name' => 'Crypto Token',
                'kind' => 'resource',
                'type' => 'Resource',
                'quantity' => 4,
                'member' => false,
            ],
        ], [[
            'id' => 42_658,
            'name' => 'Metal Ore',
            'kind' => 'resource',
            'type' => 'Resource',
            'quantity' => 1,
            'member' => false,
        ]]);
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }
}
