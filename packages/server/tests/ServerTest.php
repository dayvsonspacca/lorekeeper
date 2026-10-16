<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests;

use Lorekeeper\Server\Server;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Network\Address;

final class ServerTest extends TestCase
{
    #[Test]
    public function it_holds_name_address_and_status(): void
    {
        $address = Address::tcp('127.0.0.1', 5588);
        $server = new Server('Artix', $address, online: true, memberOnly: false);

        static::assertSame('Artix', $server->name);
        static::assertSame($address, $server->address);
        static::assertTrue($server->online);
        static::assertFalse($server->memberOnly);
    }
}
