<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Cli;

use Lorekeeper\Miner\Cli\ServerChoice;
use Lorekeeper\Miner\Server;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Network\Address;

final class ServerChoiceTest extends TestCase
{
    #[Test]
    public function it_picks_the_first_online_free_server(): void
    {
        $servers = [
            new Server('Offline', Address::tcp('a', 1), false, false),
            new Server('Sir Ver', Address::tcp('b', 2), true, true),
            new Server('Twilly', Address::tcp('c', 3), true, false),
        ];

        static::assertSame('Twilly', ServerChoice::pick($servers)->unwrap()->name);
    }

    #[Test]
    public function it_picks_a_server_by_name(): void
    {
        $servers = [
            new Server('Twilly', Address::tcp('c', 3), true, false),
            new Server('Twig', Address::tcp('d', 4), true, false),
        ];

        static::assertSame('Twig', ServerChoice::pick($servers, 'Twig')->unwrap()->name);
        static::assertTrue(ServerChoice::pick($servers, 'Artix')->isNone());
    }
}
