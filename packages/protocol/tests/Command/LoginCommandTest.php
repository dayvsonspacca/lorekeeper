<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Command;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Protocol\ClientVersion;
use Lorekeeper\Protocol\Command\LoginCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        $token = bin2hex(random_bytes(8));
        $command = new LoginCommand(new PlayerName('Hero'), $token, new ClientVersion('5.1'));

        static::assertSame(
            "<msg t='sys'><body action='login' r='0'><login z='zone_master'>"
            . '<nick><![CDATA[SPIDER#0001~Hero~5.1]]></nick>'
            . "<pword><![CDATA[{$token}]]></pword>"
            . '</login></body></msg>',
            $command->toMessage(),
        );
    }
}
