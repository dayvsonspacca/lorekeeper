<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use Lorekeeper\Client\Session\ClientVersion;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientVersionTest extends TestCase
{
    #[Test]
    public function it_reads_the_version_a_refusal_demands(): void
    {
        $demanded = ClientVersion::demandedIn(
            'Login refused: Your game client is an older version. Server requires AQW game version 5.3 or higher. Please close the AQW game window and try again.',
        );

        static::assertEquals(new ClientVersion('5.3'), $demanded);
    }

    #[Test]
    public function it_finds_no_version_in_other_refusals(): void
    {
        static::assertNull(ClientVersion::demandedIn('Login refused: Nope'));
        static::assertNull(ClientVersion::demandedIn('no open server'));
    }
}
