<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lorekeeper\Apps\Desktop\Bots\BotRecorder;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotLog;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Core\Game\Asset\Gamefile;
use Lorekeeper\Core\Game\Asset\Linkage;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Currency\Gold;
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

final class PlayBotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_recorder_follows_the_character_map_and_level(): void
    {
        $account = $this->account();
        $account->update(['name' => 'Hero2']);
        $recorder = new BotRecorder($account);
        $me = Characters::me('Hero2', level: 4, map: 'battleon');

        $me->subscribe($recorder);
        $recorder->update($me);
        static::assertSame(['battleon', 4], [$account->refresh()->map, $account->level]);

        $me->hear(new LeveledUpEvent(new PlayerLevel(6), new Experience(600)));

        static::assertSame(6, $account->refresh()->level);
    }

    #[Test]
    public function the_recorder_logs_what_matters(): void
    {
        $account = $this->account();
        $recorder = new BotRecorder($account);
        $reward = new MonsterRewardedEvent(
            new Gold(3),
            new Experience(10),
            new ClassPoints(510),
            new MonsterSpawnId(4),
        );

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

        static::assertSame(
            [
                ['Defeated a monster: +10 XP, +3 gold', 'info'],
                ['Defeated a monster: +10 XP, +3 gold', 'info'],
                ['Fell in battle',                      'warning'],
                ['Back on their feet',                  'info'],
                ['Reached level 5',                     'success'],
            ],
            $account
                ->logs()
                ->orderBy('id')
                ->get()
                ->map(static fn(BotLog $log): array => [$log->message, $log->level->value])
                ->all(),
        );
    }

    #[Test]
    public function the_recorder_keeps_what_the_bot_sees_for_the_screen(): void
    {
        $this->travelTo(now()->setTimestamp(1_800_000_000));
        $account = $this->account();
        $recorder = new BotRecorder($account);
        $me = Characters::me('Hero2', level: 12, map: 'oaklore', monsters: [new MapMonster(
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
        $scene = $account->refresh()->scene();
        static::assertNotNull($scene);
        static::assertSame(['Hero2', 12, 390, 'Mage', 'idle'], [
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
        static::assertSame(260, $account->refresh()->scene()?->monsters[0]['health']);

        $this->travel(1)->seconds();
        $recorder->heard(new MonstersHealthChangedEvent([new MonsterHealth(new MonsterSpawnId(1), new Health(40))]));
        $recorder->heard(
            new MonsterRewardedEvent(new Gold(15), new Experience(120), new ClassPoints(5), new MonsterSpawnId(1)),
        );

        $scene = $account->refresh()->scene();
        static::assertNotNull($scene);
        static::assertSame([40, 1_800_000_001], [$scene->monsters[0]['health'], $scene->monsters[0]['struck']]);
        static::assertSame(['Defeated Bonemuncher', '+120 XP · +15 gold'], [
            $scene->feed->lines[0]['text'],
            $scene->feed->lines[0]['detail'],
        ]);
        static::assertSame(120, array_sum($scene->pulse->experience(now()->getTimestamp())));
        static::assertSame(
            'Defeated Bonemuncher: +120 XP, +15 gold',
            $account->logs()->latest('id')->first()?->message,
        );
    }

    #[Test]
    public function a_bot_stopped_before_it_entered_never_logs_in(): void
    {
        $account = $this->account();
        $account->update(['status' => BotStatus::Stopping]);

        $this->artisan('bots:play', ['account' => $account->id])->assertSuccessful();

        static::assertSame(BotStatus::Offline, $account->refresh()->status);
        static::assertNull($account->started_at);
        static::assertSame([], $this->notifier->shown);
    }

    #[Test]
    public function a_bot_that_crashes_goes_offline_as_failed(): void
    {
        $account = $this->account();
        $account->update(['status' => BotStatus::Starting, 'last_server' => 'Twilly']);
        DB::table('accounts')->update([
            'password' => new Encrypter(random_bytes(32), 'aes-256-cbc')->encryptString('secret'),
        ]);

        $this->artisan('bots:play', ['account' => $account->id])->assertSuccessful();

        $account->refresh();
        static::assertSame(BotStatus::Failed, $account->status);
        static::assertSame('The MAC is invalid.', $account->result);
        static::assertNull($account->last_server);
        static::assertNull($account->pid);
        static::assertNotNull($account->stopped_at);
        static::assertSame('Failed: The MAC is invalid.', $account->logs()->latest('id')->first()?->message);
        static::assertSame([['Hero', 'Failed: The MAC is invalid.']], $this->notifier->shown);
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }
}
