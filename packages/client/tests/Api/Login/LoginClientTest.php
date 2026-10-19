<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Api\Login;

use Lorekeeper\Client\Api\Login\LoginClient;
use Lorekeeper\Client\Api\Login\LoginFailedException;
use Lorekeeper\Core\Player\PlayerName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginClientTest extends TestCase
{
    #[Test]
    public function it_reads_the_login_response(): void
    {
        $response = tempnam(sys_get_temp_dir(), prefix: 'login');
        file_put_contents(
            $response,
            data: '{"login":{"sToken":"TOKEN"},"servers":[{"sName":"Twilly","sIP":"127.0.0.1","iPort":5588,"bOnline":1,"bUpg":0}]}',
        );

        try {
            $result = new LoginClient("file://{$response}")->login(new PlayerName('Hero'), 'secret');
        } finally {
            unlink($response);
        }

        static::assertSame('TOKEN', $result->token);
        static::assertSame('Twilly', $result->servers[0]->name);
    }

    #[Test]
    public function it_fails_when_the_login_api_cannot_be_reached(): void
    {
        $this->expectException(LoginFailedException::class);
        $this->expectExceptionMessage('Could not reach the login API');

        new LoginClient('http://127.0.0.1:1/')->login(new PlayerName('Hero'), 'secret');
    }
}
