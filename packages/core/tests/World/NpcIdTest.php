<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World;

use InvalidArgumentException;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class NpcIdTest extends TestCase
{
    #[Test]
    public function it_is_identified_by_map_and_popup(): void
    {
        $id = new NpcId(new MapName('battleon'), new Linkage('Valencia_APOP'));

        static::assertSame('battleon/Valencia_APOP', (string) $id);
        static::assertEquals($id, NpcId::fromString('battleon/Valencia_APOP'));
    }

    #[Test]
    #[TestWith(['Valencia'])]
    #[TestWith(['battleon/'])]
    #[TestWith(['/Valencia'])]
    public function it_rejects_ids_without_map_and_popup(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);

        NpcId::fromString($id);
    }
}
