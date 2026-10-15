<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Tests\Api;

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
        static::assertSame(['socket' => 14], $data['sources']);
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

        $rewards = array_values(array_filter(
            $data['references'],
            static fn(array $reference): bool => $reference['type'] === 'quest.reward.item',
        ));
        static::assertSame('Specialty Pizza', $rewards[0]['subject']['name']);
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
        $application = ApiFixture::application();

        static::assertSame(404, $application->handle('/api/nope')->status);
        static::assertSame(404, $application->handle('/api/map/nowhere')->status);
        static::assertSame(404, $application->handle('/api/item/abc')->status);
    }

    /** @return array<string, mixed> */
    private static function get(string $path): array
    {
        return ApiFixture::get($path);
    }
}
