<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use DateTimeImmutable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Miner\Discovery\QuestQueue;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestQueueTest extends TestCase
{
    #[Test]
    public function it_groups_unknown_quests_by_map_and_npc(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'))->record([
            new Fact(new MapName('nulgath'), new MemberOnly()),
            new Fact(self::npc('battleon', 'Twilly'), new NpcQuest(new QuestId(11))),
            new Fact(self::npc('battleon', 'Twilly'), new NpcQuest(new QuestId(407))),
            new Fact(self::npc('battleon', 'Aria'), new NpcQuest(new QuestId(12))),
            new Fact(self::npc('nulgath', 'Nulgath'), new NpcQuest(new QuestId(870))),
            new Fact(self::npc('crashsite', 'Valencia'), new NpcQuest(new QuestId(407))),
            new Fact(self::npc('crashsite', 'Valencia'), new NpcQuest(new QuestId(5000))),
            new Fact(new QuestId(12), new QuestName('Pet Rescue')),
        ], Source::Swf);

        static::assertEquals(
            [
                'battleon' => ['Twilly_APOP' => [new QuestId(11), new QuestId(407)]],
                'crashsite' => ['Valencia_APOP' => [new QuestId(5000)]],
            ],
            new QuestQueue($store)->toLoad(),
        );
    }

    private static function npc(string $map, string $name): NpcId
    {
        return new NpcId(new MapName($map), new Linkage("{$name}_APOP"));
    }
}
