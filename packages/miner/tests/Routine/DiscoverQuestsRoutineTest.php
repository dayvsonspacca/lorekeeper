<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use DateTimeImmutable;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Miner\Discovery\QuestQueue;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Routine\DiscoverQuestsRoutine;
use Lorekeeper\Miner\Routine\MapVisit;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class DiscoverQuestsRoutineTest extends TestCase
{
    #[Test]
    public function it_asks_each_npc_for_its_quests_once_in_the_map(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $quests = array_map(
            static fn(int $id): Fact => new Fact(
                new NpcId(new MapName('battleon'), new Linkage('Twilly_APOP')),
                new NpcQuest(new QuestId($id)),
            ),
            range(1, end: 12),
        );
        new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'))->record([
            ...$quests,
            new Fact(new NpcId(new MapName('battleon'), new Linkage('Aria_APOP')), new NpcQuest(new QuestId(20))),
            new Fact(new NpcId(new MapName('yulgar'), new Linkage('Yulgar_APOP')), new NpcQuest(new QuestId(30))),
        ], Source::Swf);

        $sent = [];
        $entry = RoutineStub::entry(
            static::createStub(TCP\StreamInterface::class),
            $sent,
            RoutineStub::readySession(),
            Duration::milliseconds(200),
        );
        new DiscoverQuestsRoutine(
            $entry,
            new QuestQueue($store),
            new MapVisit(
                $entry,
                new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15')),
                static fn(string $line): null => null,
            ),
            static fn(string $line): null => null,
        )->run(Duration::zero(), 5, new SignalCancellationToken());

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%battleon%',
                '%xt%zm%getQuests%172760%1%2%3%4%5%6%7%8%9%10%',
                '%xt%zm%getQuests%172760%11%12%',
                '%xt%zm%getQuests%172760%20%',
                '%xt%zm%cmd%1%tfer%Hero%yulgar%',
                '%xt%zm%logout%1%',
            ],
            array_slice($sent, offset: 4),
        );
    }
}
