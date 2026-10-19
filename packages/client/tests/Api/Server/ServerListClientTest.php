<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Api\Server;

use Lorekeeper\Client\Api\Server\ServerListClient;
use Lorekeeper\Client\Api\Server\ServerListUnavailableException;
use Lorekeeper\Server\Server;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ServerListClientTest extends TestCase
{
    private const string SERVERS = '[{"sName":"Twilly","sIP":"sock7.aq.com","iCount":663,"iMax":1000,"bOnline":1,"iChat":0,"bUpg":0,"sLang":"xx","iPort":5593,"iLevel":0,"accessLevel":0},{"sName":"Sir Ver","sIP":"sock7.aq.com","iCount":71,"iMax":1200,"bOnline":1,"iChat":2,"bUpg":1,"sLang":"en","iPort":5589,"iLevel":0,"accessLevel":0}]';

    #[Test]
    public function it_reads_the_public_server_list(): void
    {
        $servers = self::fetch(self::SERVERS);

        static::assertSame(
            ['Twilly', 'Sir Ver'],
            array_map(static fn(Server $server): string => $server->name, $servers),
        );
        static::assertTrue($servers[0]->online);
        static::assertFalse($servers[0]->memberOnly);
        static::assertTrue($servers[1]->memberOnly);
    }

    #[Test]
    public function it_rejects_an_unexpected_response(): void
    {
        $this->expectException(ServerListUnavailableException::class);
        $this->expectExceptionMessage('Unexpected server list response.');

        self::fetch('{"error":true}');
    }

    #[Test]
    public function it_fails_when_the_server_list_cannot_be_reached(): void
    {
        $this->expectException(ServerListUnavailableException::class);
        $this->expectExceptionMessage('Could not reach the server list API');

        new ServerListClient('http://127.0.0.1:1/')->fetch();
    }

    /** @return list<Server> */
    private static function fetch(string $response): array
    {
        $file = tempnam(sys_get_temp_dir(), prefix: 'servers');
        file_put_contents($file, data: $response);

        try {
            return new ServerListClient("file://{$file}")->fetch();
        } finally {
            unlink($file);
        }
    }
}
