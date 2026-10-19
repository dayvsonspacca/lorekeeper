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
use Lorekeeper\Client\Location;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\MonsterHealth;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use Psl\Option;

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
        $me = self::me('lorekeeper03', level: 4, map: 'battleon');

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
    public function the_recorder_keeps_what_the_bot_sees_for_the_screen(): void
    {
        $this->travelTo(now()->setTimestamp(1_800_000_000));
        $run = $this->botRun();
        $recorder = new RunRecorder($run);
        $me = self::me('lorekeeper03', level: 12, map: 'oaklore', monsters: [new MapMonster(
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

    /** @param list<MapMonster> $monsters */
    private static function me(string $name, int $level, string $map, array $monsters = []): Character
    {
        return new Character(
            new SocketId(1),
            new Player(
                new PlayerName($name),
                new PlayerLevel($level),
                new Vitals(new Gauge(new Health(390), new Health(390)), new Gauge(new Mana(100), new Mana(100))),
                new PlayerClass(
                    new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), []),
                    new ClassPoints(0),
                    [],
                ),
                new PlayerInventory(new PlayerItems([])),
            ),
            new Location(
                new AreaId(1),
                new Map(new MapName($map), new Gamefile("{$map}.swf"), $monsters),
                new MapSpot(new MapCell('Enter'), MapPad::Spawn),
            ),
            CombatState::Idle,
            false,
        );
    }
}
