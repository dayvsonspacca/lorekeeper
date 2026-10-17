<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\RunRecorder;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotRunLog;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\MonsterHealth;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Map\MapPlayer;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\PlayersPresentEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;

final class RunBotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_recorder_follows_the_character_map_level_and_level_up_progress(): void
    {
        $run = $this->botRun();
        $run->account->update(['name' => 'lorekeeper03']);
        $task = $run->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 10],
            'status' => TaskStatus::Running,
            'level_from' => 4,
        ]);
        $recorder = new RunRecorder($run);
        $me = new Character(new PlayerName('lorekeeper03'));
        $me->entered(
            new InGame(
                new SocketId(1),
                new MovedToMapEvent(
                    new AreaId(1),
                    new RoomId(1),
                    new MapName('battleon'),
                    new Gamefile('b.swf'),
                    [],
                    [],
                    [],
                ),
            ),
        );
        $me->hear(new PlayersPresentEvent([new MapPlayer(new PlayerName('lorekeeper03'), new PlayerLevel(4))]));

        $recorder->working($task);
        $me->subscribe($recorder);
        $recorder->update($me);
        static::assertSame('battleon', $run->refresh()->map);
        static::assertSame(4, $run->account->refresh()->level);
        static::assertSame(0, $task->refresh()->percent());

        $me->hear(new LeveledUpEvent(new PlayerLevel(6), new Experience(600)));

        static::assertSame(6, $run->account->refresh()->level);
        static::assertSame([6, 2, 6, 33], [
            $task->refresh()->level_to,
            $task->progress_done,
            $task->progress_total,
            $task->percent(),
        ]);
    }

    #[Test]
    public function the_recorder_counts_kills_and_deaths_and_logs_what_matters(): void
    {
        $run = $this->botRun();
        $task = $run->tasks()->create([
            'script' => BotScript::LevelUp,
            'parameters' => ['level' => 6],
            'status' => TaskStatus::Running,
        ]);
        $recorder = new RunRecorder($run);
        $reward = new MonsterRewardedEvent(
            new Gold(3),
            new Experience(10),
            new ClassPoints(510),
            new MonsterSpawnId(4),
        );

        $recorder->working($task);
        $recorder->heard($reward);
        $recorder->heard(new MonstersHealthChangedEvent([new MonsterHealth(new MonsterSpawnId(4), new Health(10))]));
        $recorder->heard($reward);
        $recorder->heard(new PlayerDiedEvent());
        $recorder->heard(new ResurrectedEvent());
        $recorder->heard(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));
        $recorder->working(null);
        $recorder->heard(new PlayerDiedEvent());

        $task->refresh();
        static::assertSame([2, 1, 20, 6], [$task->kills, $task->deaths, $task->experience, $task->gold]);
        static::assertSame(
            [
                ['Earned 10 experience and 3 gold from monster 4', 'info', $task->id],
                ['Earned 10 experience and 3 gold from monster 4', 'info', $task->id],
                ['Died', 'warning', $task->id],
                ['Back to life', 'info', $task->id],
                ['Reached level 5', 'success', $task->id],
                ['Died', 'warning', null],
            ],
            $run
                ->logs()
                ->orderBy('id')
                ->get()
                ->map(static fn(BotRunLog $log): array => [$log->message, $log->level->value, $log->bot_task_id])
                ->all(),
        );
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
