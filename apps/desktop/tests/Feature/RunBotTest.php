<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\RunRecorder;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotRunLog;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterHealth;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
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
        $me = Characters::me('lorekeeper03', level: 4, map: 'battleon');

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
        $me = Characters::me('Hero', 4, 'battleon');
        $recorder->update($me);
        $me->hear(new PlayersStateChangedEvent([new PlayerState(new PlayerName('Partner'), CombatState::Dead)]));
        $recorder->update($me);
        $me->hear(new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]));
        $recorder->update($me);
        $me->hear(new ResurrectedEvent());
        $recorder->update($me);
        $recorder->heard(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));
        $recorder->working(null);
        $me->hear(new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]));
        $recorder->update($me);

        $task->refresh();
        static::assertSame([2, 1, 20, 6], [$task->kills, $task->deaths, $task->experience, $task->gold]);
        static::assertSame(
            [
                ['Defeated a monster: +10 XP, +3 gold', 'info', $task->id],
                ['Defeated a monster: +10 XP, +3 gold', 'info', $task->id],
                ['Fell in battle', 'warning', $task->id],
                ['Back on their feet', 'info', $task->id],
                ['Reached level 5', 'success', $task->id],
                ['Fell in battle', 'warning', null],
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
    public function the_recorder_keeps_what_the_bot_sees_for_the_screen(): void
    {
        $this->travelTo(now()->setTimestamp(1_800_000_000));
        $run = $this->botRun();
        $recorder = new RunRecorder($run);
        $me = Characters::me('lorekeeper03', level: 12, map: 'oaklore', monsters: [new MapMonster(
            new MonsterSpawnId(1),
            new Monster(
                new MonsterName('Bonemuncher'),
                new MonsterLevel(4),
                MonsterRace::Undead,
                new MonsterAppearance(new Gamefile('mon/bonemuncher.swf'), new Linkage('Bonemuncher')),
            ),
            new MapCell('r3'),
            new Vitals(new Gauge(new Health(260), new Health(260)), new Gauge(new Mana(0), new Mana(0))),
            CombatState::Idle,
        )]);

        $me->subscribe($recorder);
        $recorder->update($me);
        $scene = $run->refresh()->scene();
        static::assertNotNull($scene);
        static::assertSame(['lorekeeper03', 12, 390, 'Mage', 'idle'], [
            $scene->hero['name'],
            $scene->hero['level'],
            $scene->hero['maxHealth'],
            $scene->hero['class'],
            $scene->hero['state'],
        ]);
        static::assertSame('oaklore-1', $scene->place());
        static::assertSame(['Bonemuncher', 260, null], [
            $scene->monsters[0]['name'],
            $scene->monsters[0]['health'],
            $scene->monsters[0]['struck'],
        ]);

        $recorder->heard(new MonstersHealthChangedEvent([new MonsterHealth(new MonsterSpawnId(1), new Health(90))]));
        static::assertSame(260, $run->refresh()->scene()?->monsters[0]['health']);

        $this->travel(1)->seconds();
        $recorder->heard(new MonstersHealthChangedEvent([new MonsterHealth(new MonsterSpawnId(1), new Health(40))]));
        $recorder->heard(
            new MonsterRewardedEvent(new Gold(15), new Experience(120), new ClassPoints(5), new MonsterSpawnId(1)),
        );

        $scene = $run->refresh()->scene();
        static::assertNotNull($scene);
        static::assertSame([40, 1_800_000_001], [$scene->monsters[0]['health'], $scene->monsters[0]['struck']]);
        static::assertSame(['Defeated Bonemuncher', '+120 XP · +15 gold'], [
            $scene->feed->lines[0]['text'],
            $scene->feed->lines[0]['detail'],
        ]);
        static::assertSame(120, array_sum($scene->pulse->experience(now()->getTimestamp())));
        static::assertSame('Defeated Bonemuncher: +120 XP, +15 gold', $run->logs()->latest('id')->first()?->message);
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

    #[Test]
    public function a_bot_that_crashes_ends_its_run_as_failed_with_the_reason(): void
    {
        $run = $this->botRun();
        $task = $run->tasks()->create(['script' => BotScript::LevelUp, 'parameters' => ['level' => 10]]);
        DB::table('accounts')->update([
            'password' => new Encrypter(random_bytes(32), 'aes-256-cbc')->encryptString('secret'),
        ]);

        $this->artisan('bots:run', ['run' => $run->id])->assertSuccessful();

        $run->refresh();
        static::assertSame(RunStatus::Failed, $run->status);
        static::assertSame('The MAC is invalid.', $run->result);
        static::assertNull($run->pid);
        static::assertNotNull($run->finished_at);
        static::assertNotSame(TaskStatus::Running, $task->refresh()->status);
        static::assertSame('Failed: The MAC is invalid.', $run->logs()->latest('id')->first()?->message);
    }

    private function botRun(): BotRun
    {
        return Account::query()
            ->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))])
            ->runs()
            ->create();
    }
}
