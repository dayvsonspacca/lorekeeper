<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Bots\Scene\Backpack;
use Lorekeeper\Apps\Desktop\Bots\Scene\Feed;
use Lorekeeper\Apps\Desktop\Bots\Scene\FeedKind;
use Lorekeeper\Apps\Desktop\Bots\Scene\Pulse;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Filament\Pages\Accounts;
use Lorekeeper\Apps\Desktop\Livewire\KeepAlive;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Tests\ScriptFiles;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/** @mago-expect lint:too-many-methods */
final class DesktopTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_page_opens_without_logging_in(): void
    {
        $this->account();

        foreach (['/', '/preferences'] as $page) {
            $this->get($page)->assertOk()->assertSeeLivewire(KeepAlive::class);
        }
    }

    #[Test]
    public function accounts_live_on_the_home_with_no_other_page(): void
    {
        $account = $this->account();

        foreach (['/accounts', "/accounts/{$account->id}", '/bot-runs', '/bot-runs/1'] as $page) {
            $this->get($page)->assertNotFound();
        }
        $this->get('/')->assertSee('Accounts');
    }

    #[Test]
    public function the_home_shows_a_card_per_character_to_start_and_stop_its_bot(): void
    {
        $idle = $this->account();
        $busy = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8)), 'level' => 6]);
        $busy->update(['status' => BotStatus::Online, 'map' => 'icestormarena', 'started_at' => now()]);
        $this->launcher->alive[$busy->id] = true;

        Livewire::test(Accounts::class)
            ->assertSee(['Busy', 'Entering', 'icestormarena', 'Stop', 'Hero', 'Never played', 'Start'])
            ->assertDontSee(['Watch the run', 'Left off', 'Resume', 'Queue', 'Add task'])
            ->call('start', $idle->id)
            ->callAction('stop', arguments: ['account' => $busy->id]);

        static::assertSame(BotStatus::Starting, $idle->refresh()->status);
        static::assertSame([$idle->id], $this->launcher->launched);
        static::assertSame(BotStatus::Stopping, $busy->refresh()->status);
    }

    #[Test]
    public function the_home_shows_what_a_bot_in_the_game_sees(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 15:00:00', 'UTC'));
        $this->botInTheGame();

        Livewire::test(Accounts::class)->assertSee(['Hero', 'Mage', 'Fighting', 'icestormarena', '1,840', '2,300']);
    }

    #[Test]
    public function the_pages_show_no_summary_figures(): void
    {
        $this->botInTheGame();

        Livewire::test(Accounts::class)->assertDontSee([
            'Online',
            'Played today',
            'Gold today',
            'Failed in 24h',
            'Online now',
            'Runs today',
        ]);
    }

    #[Test]
    public function times_follow_the_timezone_of_the_computer(): void
    {
        config(['desktop.timezone' => 'America/Recife']);
        $this->travelTo(Carbon::parse('2026-10-21 01:00:00', 'UTC'));
        $account = $this->account();
        $account->log('Joined icestormarena');

        Livewire::test(Accounts::class)->assertSee('22:00:00');
    }

    #[Test]
    public function every_card_shows_its_fifty_latest_log_lines(): void
    {
        $account = $this->account();
        foreach (range(start: 1, end: 51) as $line) {
            $account->log(sprintf('Line %02d', $line));
        }

        Livewire::test(Accounts::class)->assertSee(['Line 51', 'Line 02'])->assertDontSee('Line 01');
    }

    #[Test]
    public function a_character_that_failed_to_log_in_is_not_shown_as_never_played(): void
    {
        $account = $this->account();
        $account->update(['status' => BotStatus::Failed, 'result' => 'wrong password', 'started_at' => now()]);

        Livewire::test(Accounts::class)
            ->assertSee('Failed: wrong password')
            ->assertDontSee(['Not played yet', 'Never played']);
    }

    #[Test]
    public function the_cards_show_only_the_characters_in_the_game_or_only_those_off(): void
    {
        $this->account();
        $busy = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8))]);
        $busy->update(['status' => BotStatus::Online, 'started_at' => now()]);

        Livewire::test(Accounts::class)
            ->assertSee(['Busy', 'Hero'])
            ->set('show', 'game')
            ->assertSee('Busy')
            ->assertDontSee('Hero')
            ->set('show', 'off')
            ->assertSee('Hero')
            ->assertDontSee('Busy');
    }

    #[Test]
    public function every_idle_bot_starts_at_once(): void
    {
        $idle = $this->account();
        $busy = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8))]);
        $busy->update(['status' => BotStatus::Online]);

        Livewire::test(Accounts::class)->callAction('startIdle');

        static::assertSame(BotStatus::Starting, $idle->refresh()->status);
        static::assertSame(BotStatus::Online, $busy->refresh()->status);
        static::assertSame([$idle->id], $this->launcher->launched);
    }

    #[Test]
    public function accounts_can_be_added_from_the_home(): void
    {
        Livewire::test(Accounts::class)
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
    public function an_account_needs_a_password_and_a_name_of_its_own(): void
    {
        $this->account();

        Livewire::test(Accounts::class)
            ->callAction('addAccount', ['name' => 'Fresh'])
            ->assertHasActionErrors(['password']);
        Livewire::test(Accounts::class)
            ->callAction('addAccount', ['name' => 'Hero', 'password' => bin2hex(random_bytes(8))])
            ->assertHasActionErrors(['name' => 'unique']);

        static::assertSame(1, Account::query()->count());
    }

    #[Test]
    public function accounts_keep_their_password_unless_a_new_one_is_typed(): void
    {
        $first = bin2hex(random_bytes(8));
        Livewire::test(Accounts::class)
            ->callAction('addAccount', ['name' => 'Hero', 'password' => $first, 'server' => 'Twilly'])
            ->assertHasNoActionErrors();
        $account = Account::query()->sole();

        Livewire::test(Accounts::class)
            ->mountAction('editAccount', ['account' => $account->id])
            ->assertSchemaStateSet(['name' => 'Hero', 'server' => 'Twilly', 'password' => null])
            ->fillForm(['name' => 'Hero', 'password' => '', 'server' => 'Espada'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $account->refresh();
        static::assertSame($first, $account->password);
        static::assertSame('Espada', $account->server);
        static::assertNotSame($first, $account->getRawOriginal('password'));
    }

    #[Test]
    public function an_account_is_deleted_only_while_its_bot_is_off(): void
    {
        $off = $this->account();
        $playing = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8))]);
        $playing->update(['status' => BotStatus::Online]);

        $home = Livewire::test(Accounts::class);
        static::assertSame(1, substr_count($home->html(), needle: 'wire:click="mountAction(\'deleteAccount\''));
        $home->assertActionHidden('deleteAccount', [
            'account' => $playing->id,
        ])->callAction('deleteAccount', arguments: ['account' => $off->id]);

        static::assertSame([$playing->id], Account::query()->pluck('id')->all());
    }

    #[Test]
    public function starting_a_bot_launches_its_process_once_at_a_time(): void
    {
        $account = $this->account();

        Livewire::test(Accounts::class)->call('start', $account->id)->call('start', $account->id);
        $account->refresh()->start();

        static::assertSame([$account->id], $this->launcher->launched);
        static::assertSame(BotStatus::Starting, $account->refresh()->status);
    }

    #[Test]
    public function stopping_asks_the_bot_to_log_out_or_settles_a_bot_that_is_gone(): void
    {
        $alive = $this->account();
        $alive->update(['status' => BotStatus::Online]);
        $this->launcher->alive[$alive->id] = true;

        Livewire::test(Accounts::class)->callAction('stop', arguments: ['account' => $alive->id]);
        static::assertSame(BotStatus::Stopping, $alive->refresh()->status);

        $gone = Account::query()->create(['name' => 'Gone', 'password' => bin2hex(random_bytes(8))]);
        $gone->update(['status' => BotStatus::Online]);

        Livewire::test(Accounts::class)->callAction('stop', arguments: ['account' => $gone->id]);
        static::assertSame(BotStatus::Offline, $gone->refresh()->status);
    }

    #[Test]
    public function stopping_a_bot_that_already_ended_leaves_it_ended(): void
    {
        $account = $this->account();
        $account->update(['status' => BotStatus::Failed, 'result' => 'Disconnected by the server']);
        $stale = Account::query()->findOrFail($account->id);
        $stale->status = BotStatus::Online;

        $stale->stop($this->launcher);

        static::assertSame(BotStatus::Failed, $account->refresh()->status);
    }

    #[Test]
    public function bots_left_playing_by_a_closed_app_end_as_interrupted(): void
    {
        $playing = $this->account();
        $playing->update(['status' => BotStatus::Online, 'pid' => 4242]);
        $off = Account::query()->create(['name' => 'Off', 'password' => bin2hex(random_bytes(8))]);

        Account::interrupted();

        static::assertSame(BotStatus::Failed, $playing->refresh()->status);
        static::assertSame('interrupted', $playing->result);
        static::assertNull($playing->pid);
        static::assertSame(BotStatus::Offline, $off->refresh()->status);
    }

    private function botInTheGame(): Account
    {
        $account = $this->account();
        $now = now()->getTimestamp();
        $account->update([
            'status' => BotStatus::Online,
            'started_at' => now()->subMinutes(10),
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

        return $account;
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

    #[Test]
    public function a_script_is_played_from_the_home_starting_a_bot_that_is_off(): void
    {
        $off = $this->account();
        $online = Account::query()->create(['name' => 'Busy', 'password' => bin2hex(random_bytes(8))]);
        $online->update(['status' => BotStatus::Online, 'started_at' => now()]);
        $script = ScriptFiles::crypto();

        Livewire::test(Accounts::class)
            ->callAction('playScript', ['script' => $script->id], arguments: ['account' => $off->id])
            ->callAction('playScript', ['script' => $script->id], arguments: ['account' => $online->id]);

        static::assertSame([$script->id, BotStatus::Starting], [$off->refresh()->script_id, $off->status]);
        static::assertSame([$script->id, BotStatus::Online], [$online->refresh()->script_id, $online->status]);
        static::assertSame([$off->id], $this->launcher->launched);
    }

    #[Test]
    public function only_scripts_ready_to_play_are_offered(): void
    {
        $account = $this->account();
        $broken = Script::query()->create(['name' => 'Half drawn']);

        Livewire::test(Accounts::class)
            ->callAction('playScript', ['script' => $broken->id], arguments: ['account' => $account->id])
            ->assertHasActionErrors(['script']);

        static::assertNull($account->refresh()->script_id);
    }

    #[Test]
    public function the_home_shows_the_script_a_bot_plays_and_stops_it_without_logging_out(): void
    {
        $account = $this->account();
        $account->update([
            'status' => BotStatus::Online,
            'started_at' => now(),
            'script_id' => ScriptFiles::crypto()->id,
            'step' => 'Join boxes',
        ]);
        $this->launcher->alive[$account->id] = true;

        Livewire::test(Accounts::class)
            ->assertSee(['Crypto Token daily', 'Join boxes', 'Stop script'])
            ->callAction('stopScript', arguments: ['account' => $account->id]);

        static::assertSame([null, null, BotStatus::Online], [
            $account->refresh()->script_id,
            $account->step,
            $account->status,
        ]);
    }

    #[Test]
    public function a_bot_that_is_logging_out_takes_no_script(): void
    {
        $account = $this->account();
        $account->update(['status' => BotStatus::Stopping]);

        $account->play(ScriptFiles::crypto());

        static::assertNull($account->refresh()->script_id);
        static::assertSame([], $this->launcher->launched);
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }
}
