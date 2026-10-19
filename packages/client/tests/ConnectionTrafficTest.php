<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\TrafficDirection;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;
use Psl\TCP;

final class ConnectionTrafficTest extends TestCase
{
    #[Test]
    public function its_observers_hear_what_goes_out_and_what_comes_in_unpacked(): void
    {
        /** @var ObserverInterface<Connection> $heard */
        $heard = new class implements ObserverInterface {
            /** @var list<string> */
            public array $lines = [];

            #[Override]
            public function update(SubjectInterface $subject): void
            {
                $traffic = $subject instanceof Connection ? $subject->traffic() : null;
                if ($traffic !== null) {
                    $this->lines[] = ($traffic->direction === TrafficDirection::Sent ? '> ' : '< ') . $traffic->message;
                }
            }
        };
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream
            ->method('read')
            ->willReturn('Z' . base64_encode((string) gzcompress('{"b":{"o":{"cmd":"moveToArea"}}}')) . "\0");
        $stream->method('reachedEndOfDataSource')->willReturn(false);
        $connection = new Connection($stream);
        $connection->subscribe($heard);

        $connection->send(new LogoutCommand());
        $connection->receive();

        static::assertSame(['> %xt%zm%logout%1%', '< {"b":{"o":{"cmd":"moveToArea"}}}'], $heard->lines);
    }
}
