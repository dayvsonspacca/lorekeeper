<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\RoutineSteps;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Desktop\Filament\Resources\Routines\Pages\ManageRoutines;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Apps\Desktop\Models\Routine;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RoutinesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_routine_is_saved_as_a_list_of_tasks(): void
    {
        $this->get('/routines')->assertOk()->assertSee('No routines yet');
        Repeater::fake();

        Livewire::test(ManageRoutines::class)
            ->callAction('create', [
                'name' => 'Go to level 100',
                'tasks' => RoutineSteps::levelUp(95, 100, 5, 'Lucky'),
            ])
            ->assertHasNoActionErrors();

        $routine = Routine::query()->sole();
        static::assertSame('Go to level 100', $routine->name);
        static::assertSame(
            [
                ['script' => 'level-up', 'parameters' => ['level' => 95]],
                ['script' => 'enhance', 'parameters' => ['type' => 'Lucky']],
                ['script' => 'level-up', 'parameters' => ['level' => 100]],
                ['script' => 'enhance', 'parameters' => ['type' => 'Lucky']],
            ],
            $routine->steps(),
        );
    }

    #[Test]
    public function queuing_a_routine_leaves_out_what_the_character_has_already_done(): void
    {
        $routine = Routine::query()->create([
            'name' => 'Go to level 100',
            'tasks' => RoutineSteps::levelUp(5, 30, 5, 'Lucky'),
        ]);
        $account = Account::query()->create(['name' => 'lorekeeper03', 'password' => bin2hex(random_bytes(8))]);
        $account->update(['level' => 24]);
        $run = $account->runs()->create();
        $run->update(['status' => RunStatus::Running]);
        Repeater::fake();

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->mountAction('addTask')
            ->fillForm(['routine' => $routine->id])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        static::assertSame(
            [
                'Enhance the gear · with Lucky',
                'Level up · to level 25',
                'Enhance the gear · with Lucky',
                'Level up · to level 30',
                'Enhance the gear · with Lucky',
            ],
            $run
                ->tasks()
                ->orderBy('id')
                ->get()
                ->map(static fn(BotTask $task): string => $task->title())
                ->all(),
        );
    }

    #[Test]
    public function picking_a_routine_fills_the_list_and_queues_its_tasks_in_order(): void
    {
        $routine = Routine::query()->create([
            'name' => 'Dailies',
            'tasks' => [
                ['script' => 'crypto-token', 'parameters' => []],
                ['script' => 'elders-blood', 'parameters' => []],
            ],
        ]);
        $run = Account::query()
            ->create(['name' => 'lorekeeper03', 'password' => bin2hex(random_bytes(8))])
            ->runs()
            ->create();
        $run->update(['status' => RunStatus::Running]);
        Repeater::fake();

        Livewire::test(ViewBotRun::class, ['record' => $run->id])
            ->mountAction('addTask')
            ->fillForm(['routine' => $routine->id])
            ->assertSchemaStateSet(['tasks' => $routine->steps()])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        static::assertSame(
            [BotScript::CryptoToken, BotScript::EldersBlood],
            $run
                ->tasks()
                ->orderBy('id')
                ->get()
                ->map(static fn(BotTask $task): BotScript => $task->script)
                ->all(),
        );
    }
}
