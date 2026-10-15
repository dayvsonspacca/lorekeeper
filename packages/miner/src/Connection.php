<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Lorekeeper\Miner\Exception\PeerClosedException;
use Lorekeeper\Protocol\Command\Command;
use Lorekeeper\Protocol\Message\Message;
use Lorekeeper\Protocol\Message\MessageParser;
use Psl\IO;
use Psl\Option;
use Psl\TCP;

final class Connection
{
    private const string TERMINATOR = "\0";

    private readonly IO\Reader $reader;

    public function __construct(
        private readonly TCP\StreamInterface $stream,
    ) {
        $this->reader = new IO\Reader($stream);
    }

    public static function open(Server $server): self
    {
        return new self(TCP\connect($server->address->host, $server->address->port ?? 0));
    }

    public function send(Command $command): void
    {
        $this->stream->writeAll($command->toMessage() . self::TERMINATOR);
    }

    /**
     * @throws PeerClosedException
     *
     * @return Option\Option<Message>
     */
    public function receive(): Option\Option
    {
        try {
            $raw = $this->reader->readUntil(self::TERMINATOR);
        } catch (IO\Exception\RuntimeException $exception) {
            throw new PeerClosedException(
                'Connection error while receiving: ' . $exception->getMessage(),
                previous: $exception,
            );
        }

        if ($raw === null) {
            throw new PeerClosedException('Peer closed the connection.');
        }

        return MessageParser::parse($raw);
    }

    public function close(): void
    {
        $this->stream->close();
    }
}
