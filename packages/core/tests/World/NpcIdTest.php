<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World;

use InvalidArgumentException;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class NpcIdTest extends TestCase
{
    #[Test]
    public function it_is_identified_by_map_and_name(): void
    {
        $id = new NpcId(new MapName('battleon'), new NpcName('Valencia'));

        static::assertSame('battleon/Valencia', (string) $id);
        static::assertEquals($id, NpcId::fromString('battleon/Valencia'));
    }

    #[Test]
    #[TestWith(['Valencia'])]
    #[TestWith(['battleon/'])]
    #[TestWith(['/Valencia'])]
    public function it_rejects_ids_without_map_and_name(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);

        NpcId::fromString($id);
    }
}
