<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Exploring;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Wiki\Exploring\Scribe;
use Lorekeeper\Apps\Wiki\Models\Item;
use Lorekeeper\Apps\Wiki\Tests\GameMessages;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use Lorekeeper\Client\Traffic;
use Lorekeeper\Client\TrafficDirection;

final class ScribeTest extends TestCase
{
    use GameMessages;
    use RefreshDatabase;

    public function testItKeepsWhatTheServerTells(): void
    {
        $scribe = new Scribe();
        $scribe->hear(new Traffic(TrafficDirection::Received, self::encoded(self::shop([self::gladiator()]))));

        static::assertSame(
            'Gladiator of Circumstance',
            Item::query()
                ->where('aqw_id', 16_416)
                ->sole()
                ->name,
        );
        static::assertSame(2, $scribe->facts());
    }

    public function testItReadsCompressedMessages(): void
    {
        $compressed = 'Z' . base64_encode((string) gzcompress(self::encoded(self::shop([self::gladiator()]))));
        new Scribe()->hear(new Traffic(TrafficDirection::Received, $compressed));

        static::assertNotNull(Item::query()->where('aqw_id', 16_416)->first());
    }

    public function testItIgnoresWhatTheClientSends(): void
    {
        $scribe = new Scribe();
        $scribe->hear(new Traffic(TrafficDirection::Sent, self::encoded(self::shop([self::gladiator()]))));
        $scribe->hear(new Traffic(TrafficDirection::Received, '%xt%server%-1%Profanity filter On.%'));

        static::assertSame(0, $scribe->facts());
        static::assertSame(0, Item::query()->count());
    }
}
