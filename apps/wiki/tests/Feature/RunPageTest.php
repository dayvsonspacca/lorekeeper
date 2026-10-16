<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Wiki\Livewire\RunEvents;
use Lorekeeper\Apps\Wiki\Livewire\RunLog;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RunPageTest extends TestCase
{
    use CreatesBots;
    use RefreshDatabase;

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

        $run->events()->create(['name' => 'ClassChanged', 'summary' => 'Socket 7 is Shaman (Caster, 2689 CP)']);

        Livewire::test(RunEvents::class, ['run' => $run])
            ->assertSee('Socket 7 equipped item 31660')
            ->assertSee('%xt%uotls%-1%hero%afk:true%')
            ->call('toggle', 'Unrecognized')
            ->assertDontSee('Socket 7 equipped item 31660')
            ->call('toggle', 'ClassChanged')
            ->assertSee('Shaman')
            ->assertDontSee('Socket 7 equipped item 31660')
            ->call('toggle', 'Unrecognized')
            ->call('toggle', 'ClassChanged')
            ->assertSee('Socket 7 equipped item 31660')
            ->set('search', 'afk:true')
            ->assertSee('uotls')
            ->assertDontSee('Shaman')
            ->set('search', 'nothing like this')
            ->assertSee('No events match the filter.')
            ->call('showAll')
            ->assertSet('search', '')
            ->assertSee('Shaman');
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

        $run->update(['map' => 'forest']);
        $log->call('refreshRun')->assertDispatched('run-changed');

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->assertActionVisible('stop')
            ->tap(static fn() => $run->update(['status' => RunStatus::Stopped, 'finished_at' => now()]))
            ->dispatch('run-changed')
            ->assertActionHidden('stop')
            ->assertActionVisible('runAgain');
    }
}
