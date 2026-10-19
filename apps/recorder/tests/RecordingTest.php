<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder\Tests;

use Lorekeeper\Apps\Recorder\Recording;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Timestamp;
use Psl\TCP;

final class RecordingTest extends TestCase
{
    #[Test]
    public function it_records_each_message_sent_and_received_with_the_token_hidden(): void
    {
        $path = sys_get_temp_dir() . '/' . uniqid('recording-', more_entropy: true) . '.jsonl';
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturn("<msg t='sys'><body action='logOK' r='0'>t0k3n</body></msg>\0");
        $stream->method('reachedEndOfDataSource')->willReturn(false);
        $recording = new Recording($path, 't0k3n', clock: static fn(): Timestamp => Timestamp::fromParts(
            1_792_434_600,
        ));
        $connection = new Connection($stream);
        $connection->subscribe($recording);

        $connection->send(new LogoutCommand());
        $connection->receive();

        static::assertSame(2, $recording->messages());
        static::assertSame(
            [
                ['at' => '2026-10-19T18:30:00.000Z', 'way' => 'sent', 'message' => '%xt%zm%logout%1%'],
                [
                    'at' => '2026-10-19T18:30:00.000Z',
                    'way' => 'received',
                    'message' => "<msg t='sys'><body action='logOK' r='0'><token></body></msg>",
                ],
            ],
            array_map(
                static fn(string $line): mixed => json_decode($line, associative: true, flags: JSON_THROW_ON_ERROR),
                (array) file($path, FILE_IGNORE_NEW_LINES),
            ),
        );

        unlink($path);
    }
}
