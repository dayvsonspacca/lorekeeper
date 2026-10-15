<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Login;

use Lorekeeper\Miner\Exception\LoginFailedException;
use Lorekeeper\Miner\Login\LoginResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginResultTest extends TestCase
{
    private const string SUCCESS = '{"login":{"bSuccess":1,"userid":1,"iAccess":0,"iUpg":0,"sMsg":"success","sToken":"TOKEN","strCountryCode":"BR"},"servers":[{"sName":"Twilly","sIP":"sock7.aq.com","iCount":658,"iMax":1000,"bOnline":1,"iChat":0,"bUpg":0,"sLang":"xx","iPort":5593,"iLevel":0,"accessLevel":0},{"sName":"Sir Ver","sIP":"sock7.aq.com","iCount":76,"iMax":1200,"bOnline":1,"iChat":0,"bUpg":1,"sLang":"xx","iPort":5589,"iLevel":0,"accessLevel":0},{"sName":"Yokai (SEA)","sIP":"asia.game.artix.com","iCount":0,"iMax":1000,"bOnline":0,"iChat":0,"bUpg":0,"sLang":"xx","iPort":5588,"iLevel":0,"accessLevel":0}],"polldata":[]}';

    private const string FAILURE = '{"bSuccess":0,"sMsg":"The username and password you entered did not match.<br/>Please check the spelling and try again."}';

    #[Test]
    public function it_reads_the_token(): void
    {
        static::assertSame('TOKEN', LoginResult::fromJson(self::SUCCESS)->token);
    }

    #[Test]
    public function it_reads_the_servers(): void
    {
        $servers = LoginResult::fromJson(self::SUCCESS)->servers;

        static::assertSame(
            ['Twilly', 'Sir Ver', 'Yokai (SEA)'],
            array_map(static fn($server) => $server->name, $servers),
        );
        static::assertSame('sock7.aq.com', $servers[0]->address->host);
        static::assertSame(5593, $servers[0]->address->port);
        static::assertTrue($servers[0]->online);
        static::assertFalse($servers[0]->memberOnly);
        static::assertTrue($servers[1]->memberOnly);
        static::assertFalse($servers[2]->online);
    }

    #[Test]
    public function it_fails_with_the_server_message(): void
    {
        $this->expectException(LoginFailedException::class);
        $this->expectExceptionMessage(
            'The username and password you entered did not match.Please check the spelling and try again.',
        );

        LoginResult::fromJson(self::FAILURE);
    }

    #[Test]
    public function it_fails_on_unexpected_responses(): void
    {
        $this->expectException(LoginFailedException::class);

        LoginResult::fromJson('<html>maintenance</html>');
    }
}
