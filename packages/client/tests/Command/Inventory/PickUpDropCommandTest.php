<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Inventory;

use Lorekeeper\Client\Command\Inventory\PickUpDropCommand;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PickUpDropCommandTest extends TestCase
{
    #[Test]
    public function it_asks_for_the_dropped_item_on_behalf_of_the_player(): void
    {
        static::assertSame(
            '%xt%zm%getDrop%61208%42657%',
            new PickUpDropCommand(new SocketId(61_208), new ItemId(42_657))->toMessage(),
        );
    }
}
