<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Exception\PeerClosedException;
use Lorekeeper\Client\Message\MessageParser;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Server;
use Psl\IO;
use Psl\Option;
use Psl\TCP;

final class Connection
{
    private const string TERMINATOR = "\0";

    private readonly IO\Reader $reader;

    private ?string $closedBecause = null;

    private bool $closedHere = false;

    public function __construct(
        private readonly TCP\StreamInterface $stream,
    ) {
        $this->reader = new IO\Reader($stream);
    }

    public static function open(Server $server): self
    {
        return new self(TCP\connect($server->address->host, $server->address->port ?? 0));
    }

    /** @throws PeerClosedException */
    public function send(Command $command): void
    {
        if ($this->closedBecause !== null) {
            throw new PeerClosedException($this->closedBecause);
        }

        try {
            $this->stream->writeAll($command->toMessage() . self::TERMINATOR);
        } catch (IO\Exception\RuntimeException $exception) {
            throw new PeerClosedException(
                'Connection error while sending: ' . $exception->getMessage(),
                previous: $exception,
            );
        }
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
            $this->closedBecause = 'Connection error while receiving: ' . $exception->getMessage();

            throw new PeerClosedException($this->closedBecause, previous: $exception);
        }

        if ($raw === null) {
            $this->closedBecause = 'The server closed the connection.';

            throw new PeerClosedException($this->closedBecause);
        }

        return MessageParser::parse($raw);
    }

    public function close(): void
    {
        $this->closedHere = true;
        $this->stream->close();
    }

    public function closedHere(): bool
    {
        return $this->closedHere;
    }
}
