<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\QueuedTasks;
use Lorekeeper\Apps\Desktop\Bots\RunRecorder;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Session\JobStatus;
use Lorekeeper\Client\Bot\Session\Outcome;
use Lorekeeper\Client\Bot\Task\GoToMapTask;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;

final class RunBotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_queue_hands_out_pending_tasks_and_fails_the_ones_that_cannot_be_played(): void
    {
        $run = $this->botRun();
        $broken = $run->tasks()->create(['script' => BotScript::AskQuests, 'parameters' => ['map' => 'battleon']]);
        $good = $run->tasks()->create(['script' => BotScript::GoToMap, 'parameters' => ['map' => 'forest']]);
        $queue = new QueuedTasks($run);

        $job = $queue->next();

        static::assertNotNull($job);
        static::assertSame($good->id, $job->id);
        static::assertEquals(new GoToMapTask(new MapName('forest')), $job->task);
        static::assertSame(TaskStatus::Failed, $broken->refresh()->status);
        static::assertFalse($queue->cancelled($job));

        $good->update(['status' => TaskStatus::Cancelling]);
        static::assertTrue($queue->cancelled($job));
    }

    #[Test]
    public function the_recorder_keeps_the_log_the_map_and_how_each_task_goes(): void
    {
        $run = $this->botRun();
        $task = $run->tasks()->create([
            'script' => BotScript::OpenShops,
            'parameters' => [
                'map' => 'battleon',
                'shops' => [31, 609],
            ],
        ]);
        $job = new QueuedTasks($run)->next();
        static::assertNotNull($job);
        $recorder = new RunRecorder($run);

        $recorder->started($job);
        $recorder->moved(new Position(new MapName('battleon'), new AreaId(1)));
        $recorder->progressed($job, new Progress(1, 2));
        static::assertSame(50, $task->refresh()->percent());
        static::assertSame(TaskStatus::Running, $task->status);

        $recorder->said('Opened a shop', LogLevel::Info);
        $recorder->finished($job, new Outcome(JobStatus::Done, 'Opened 2 of 2 shops'));
        $recorder->said('Waiting for tasks in battleon', LogLevel::Info);

        static::assertSame(TaskStatus::Done, $task->refresh()->status);
        static::assertSame('Opened 2 of 2 shops', $task->result);
        static::assertSame('battleon', $run->refresh()->map);
        static::assertSame([$task->id, null], $run->logs()->orderBy('id')->pluck('bot_task_id')->all());
    }

    #[Test]
    public function a_run_stopped_before_its_bot_started_never_logs_in(): void
    {
        $run = $this->botRun();
        $run->update(['status' => RunStatus::Stopping]);

        $this->artisan('bots:run', ['run' => $run->id])->assertSuccessful();

        static::assertSame(RunStatus::Stopped, $run->refresh()->status);
        static::assertNull($run->started_at);
    }

    private function botRun(): BotRun
    {
        return Account::query()
            ->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))])
            ->runs()
            ->create();
    }
}
