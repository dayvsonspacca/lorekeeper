<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Tests\Api;

use DateTimeImmutable;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Observation;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Wiki\Api\Application;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    #[Test]
    public function it_summarizes_what_is_known(): void
    {
        $data = self::get('/api/overview');

        static::assertSame(1, $data['counts']['map']);
        static::assertSame(1, $data['counts']['monster']);
        static::assertSame(['socket' => 10], $data['sources']);
        static::assertContains('Specialty Pizza', array_map(
            static fn(array $page): string => $page['subject']['name'],
            $data['newPages'],
        ));
    }

    #[Test]
    public function it_lists_every_subject_for_search(): void
    {
        static::assertContains(
            ['kind' => 'monster', 'id' => '2', 'name' => 'Boss Zardman'],
            self::get('/api/subjects'),
        );
    }

    #[Test]
    public function it_lists_a_kind_with_its_facts(): void
    {
        $data = self::get('/api/map');

        static::assertSame('map', $data['kind']);
        static::assertSame(['forest'], array_column($data['rows'], 'id'));
        static::assertSame([2], $data['rows'][0]['facts']['map.id']);
    }

    #[Test]
    public function it_details_a_subject_with_resolved_references(): void
    {
        $data = self::get('/api/map/forest');

        static::assertSame('forest', $data['subject']['id']);
        $monster = array_values(array_filter(
            $data['facts'],
            static fn(array $f): bool => $f['type'] === 'map.monster',
        ))[0];
        static::assertSame(['kind' => 'monster', 'id' => '2', 'name' => 'Boss Zardman'], $monster['ref']);
    }

    #[Test]
    public function it_shows_what_points_at_a_subject(): void
    {
        $data = self::get('/api/item/100');

        static::assertSame('quest.reward.item', $data['references'][0]['type']);
        static::assertSame('Specialty Pizza', $data['references'][0]['subject']['name']);
    }

    #[Test]
    public function it_connects_npcs_to_their_maps_and_quests(): void
    {
        $npc = self::get('/api/npc/forest%2FTwilly');

        static::assertSame(['kind' => 'npc', 'id' => 'forest/Twilly', 'name' => 'Twilly'], $npc['subject']);
        static::assertSame(
            ['kind' => 'quest', 'id' => '14', 'name' => 'Specialty Pizza'],
            array_values(array_filter(
                $npc['facts'],
                static fn(array $f): bool => $f['type'] === 'npc.quest',
            ))[0]['ref'],
        );
        static::assertSame('forest', $npc['references'][0]['subject']['id']);

        $quest = self::get('/api/quest/14');

        static::assertContains('Twilly', array_map(
            static fn(array $reference): string => $reference['subject']['name'],
            array_filter($quest['references'], static fn(array $reference): bool => $reference['type'] === 'npc.quest'),
        ));
    }

    #[Test]
    public function it_builds_the_atlas(): void
    {
        $data = self::get('/api/atlas');

        static::assertSame([['source' => 'forest', 'target' => 'fableforest']], $data['links']);
        static::assertSame(['forest', 'fableforest'], array_column($data['nodes'], 'id'));
        static::assertSame(1, $data['nodes'][0]['monsters']);
    }

    #[Test]
    public function it_answers_not_found(): void
    {
        $application = self::application();

        static::assertSame(404, $application->handle('/api/nope')->status);
        static::assertSame(404, $application->handle('/api/map/nowhere')->status);
        static::assertSame(404, $application->handle('/api/item/abc')->status);
    }

    /** @return array<string, mixed> */
    private static function get(string $path): array
    {
        $response = self::application()->handle($path);
        static::assertSame(200, $response->status);

        return json_decode($response->body(), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    private static function application(): Application
    {
        $codecs = DefaultCodecs::create();
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), $codecs);
        $at = new DateTimeImmutable('2026-10-15T14:03:00+00:00');

        $store->record(array_map(static fn(Fact $fact): Observation => new Observation($fact, Source::Socket, $at), [
            new Fact(new MapName('forest'), new MapId(2)),
            new Fact(new MapName('forest'), new MapMonster(new MonsterId(2))),
            new Fact(new MapName('forest'), new MapLink(new MapName('fableforest'))),
            new Fact(new MonsterId(2), new MonsterName('Boss Zardman')),
            new Fact(new ItemId(100), new ItemName('Special Hair Gel')),
            new Fact(new QuestId(14), new QuestName('Specialty Pizza')),
            new Fact(new QuestId(14), new ItemReward(new ItemId(100), new DropRate(12.5), 1)),
            new Fact(new MapName('forest'), new MapNpc(new NpcId(new MapName('forest'), new NpcName('Twilly')))),
            new Fact(new NpcId(new MapName('forest'), new NpcName('Twilly')), new NpcName('Twilly')),
            new Fact(new NpcId(new MapName('forest'), new NpcName('Twilly')), new NpcQuest(new QuestId(14))),
        ]));

        return new Application($store, $codecs);
    }
}
