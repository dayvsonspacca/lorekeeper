<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;
use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use Lorekeeper\Client\Bot\Log\LogLevel;
use PHPUnit\Framework\Attributes\Test;

final class BotModelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function account_passwords_are_encrypted_at_rest_and_never_serialized(): void
    {
        $secret = bin2hex(random_bytes(8));
        $account = Account::query()->create(['name' => 'Hero', 'password' => $secret]);

        $stored = DB::table('accounts')->value('password');

        static::assertIsString($stored);
        static::assertStringNotContainsString($secret, $stored);
        static::assertSame($secret, $account->fresh()?->password);
        static::assertArrayNotHasKey('password', $account->toArray());
    }

    #[Test]
    public function runs_start_pending_and_keep_their_tasks_and_log(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $run = BotRun::query()->findOrFail($account->runs()->create()->id);
        $first = $run->tasks()->create(['script' => BotScript::GoToMap, 'parameters' => ['map' => 'forest']]);
        $second = $run->tasks()->create([
            'script' => BotScript::AskQuests,
            'parameters' => ['map' => 'forest', 'quests' => ['3']],
        ]);

        $run->log('Logging in as Hero');
        $run->log('Now in forest', LogLevel::Success, $first);

        static::assertSame(RunStatus::Pending, $run->status);
        static::assertSame('Hero', $run->account->name);
        static::assertSame($first->id, $run->nextTask()?->id);
        static::assertSame('Go to map · forest', $first->title());
        static::assertSame([null, $first->id], $run->logs()->orderBy('id')->pluck('bot_task_id')->all());

        $first->finish(TaskStatus::Done, 'Now in forest');
        static::assertSame($second->id, $run->nextTask()?->id);

        $second->update(['status' => TaskStatus::Running]);
        $run->tasks()->create(['script' => BotScript::GoToMap, 'parameters' => ['map' => 'battleon']]);
        $run->closeTasks('the run ended');

        static::assertSame(
            [TaskStatus::Done, TaskStatus::Failed, TaskStatus::Cancelled],
            $run
                ->tasks()
                ->orderBy('id')
                ->get()
                ->map(static fn($task) => $task->status)
                ->all(),
        );
        static::assertNull($run->nextTask());
    }

    #[Test]
    public function only_pending_running_and_stopping_runs_are_active(): void
    {
        static::assertSame(
            [RunStatus::Pending, RunStatus::Running, RunStatus::Stopping],
            array_values(array_filter(RunStatus::cases(), static fn(RunStatus $status): bool => $status->isActive())),
        );
    }
}
