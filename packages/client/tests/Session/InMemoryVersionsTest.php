<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use Lorekeeper\Client\Session\ClientVersion;
use Lorekeeper\Client\Session\InMemoryVersions;
use Lorekeeper\Client\Session\Login;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryVersionsTest extends TestCase
{
    #[Test]
    public function it_starts_from_the_version_it_is_given_or_the_known_one(): void
    {
        static::assertEquals(new ClientVersion(Login::VERSION), new InMemoryVersions()->current());
        static::assertEquals(new ClientVersion('5.2'), new InMemoryVersions(new ClientVersion('5.2'))->current());
    }

    #[Test]
    public function it_keeps_the_version_it_learns(): void
    {
        $versions = new InMemoryVersions();

        $versions->learn(new ClientVersion('5.4'));

        static::assertEquals(new ClientVersion('5.4'), $versions->current());
    }
}
