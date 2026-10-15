<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Tests\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorldLinksTest extends TestCase
{
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
    public function it_connects_shops_to_their_npcs_and_items(): void
    {
        $shop = self::get('/api/shop/31');

        static::assertSame(['kind' => 'shop', 'id' => '31', 'name' => 'Battleon Assorted shop'], $shop['subject']);
        static::assertContains(
            ['kind' => 'item', 'id' => '100', 'name' => 'Special Hair Gel'],
            array_column($shop['facts'], 'ref'),
        );
        static::assertSame('forest/Twilly', $shop['references'][0]['subject']['id']);

        $sold = array_values(array_filter(
            self::get('/api/item/100')['references'],
            static fn(array $reference): bool => $reference['type'] === 'shop.item',
        ));
        static::assertSame(
            [100, ['item' => 200, 'costs' => [['item' => 100, 'quantity' => 3]]]],
            array_column($sold, 'value'),
        );
    }

    /** @return array<string, mixed> */
    private static function get(string $path): array
    {
        return ApiFixture::get($path);
    }
}
