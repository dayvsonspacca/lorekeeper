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
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Map\MapPlayer;
use Lorekeeper\Server\Event\Map\PlayersPresentEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\Message\JsonMessage;
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
    public function the_recorder_counts_kills_rewards_deaths_and_levels(): void
    {
        $run = $this->botRun();
        $run->account->update(['name' => 'lorekeeper03', 'level' => 4]);
        $task = $run->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 6]]);
        $job = new QueuedTasks($run)->next();
        static::assertNotNull($job);
        $recorder = new RunRecorder($run);
        $reward = new MonsterRewardedEvent(
            new Gold(3),
            new Experience(10),
            new ClassPoints(510),
            new MonsterSpawnId(4),
        );
        $message = JsonMessage::from('{"b":{"o":{"cmd":"addGoldExp"}}}')->unwrap();

        $recorder->started($job);
        $recorder->heard($message, [$reward]);
        $recorder->heard($message, [$reward, new PlayerDiedEvent()]);
        $recorder->heard($message, [new LeveledUpEvent(new PlayerLevel(5), new Experience(500))]);

        $task->refresh();
        static::assertSame([2, 1, 20, 6, 4], [
            $task->kills,
            $task->deaths,
            $task->experience,
            $task->gold,
            $task->level_from,
        ]);
        static::assertSame(5, $run->account->refresh()->level);

        $recorder->heard($message, [new PlayersPresentEvent([
            new MapPlayer(new PlayerName('Someone'), new PlayerLevel(90)),
            new MapPlayer(new PlayerName('LoreKeeper03'), new PlayerLevel(7)),
        ])]);
        static::assertSame(7, $run->account->refresh()->level);
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
