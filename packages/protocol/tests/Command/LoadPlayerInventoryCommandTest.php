<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Command;

use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Command\LoadPlayerInventoryCommand;
use Lorekeeper\Protocol\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoadPlayerInventoryCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        $command = new LoadPlayerInventoryCommand(new AreaId(42), new SocketId(99_887));

        static::assertSame('%xt%zm%retrieveInventory%42%99887%', $command->toMessage());
    }
}
