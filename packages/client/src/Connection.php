<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Server;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\NullCancellationToken;
use Psl\IO;
use Psl\Observer\SubjectInterface;
use Psl\Option;
use Psl\TCP;

final class Connection implements SubjectInterface
{
    use Observable;

    private const string TERMINATOR = "\0";

    private readonly IO\Reader $reader;

    private ?string $closedBecause = null;

    private bool $closedHere = false;

    private ?Traffic $traffic = null;

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

        $message = $command->toMessage();
        try {
            $this->stream->writeAll($message . self::TERMINATOR);
        } catch (IO\Exception\RuntimeException $exception) {
            throw new PeerClosedException(
                'Connection error while sending: ' . $exception->getMessage(),
                previous: $exception,
            );
        }

        $this->witnessed(TrafficDirection::Sent, $message);
    }

    /**
     * @throws PeerClosedException
     *
     * @return Option\Option<Message>
     */
    public function receive(CancellationTokenInterface $cancellation = new NullCancellationToken()): Option\Option
    {
        try {
            $raw = $this->reader->readUntil(self::TERMINATOR, $cancellation);
        } catch (IO\Exception\RuntimeException $exception) {
            $this->closedBecause = 'Connection error while receiving: ' . $exception->getMessage();

            throw new PeerClosedException($this->closedBecause, previous: $exception);
        }

        if ($raw === null) {
            $this->closedBecause = 'The server closed the connection.';

            throw new PeerClosedException($this->closedBecause);
        }

        $message = MessageParser::parse($raw);
        $this->witnessed(
            TrafficDirection::Received,
            $message->map(static fn(Message $parsed): string => $parsed->raw)->unwrapOr($raw),
        );

        return $message;
    }

    public function traffic(): ?Traffic
    {
        return $this->traffic;
    }

    private function witnessed(TrafficDirection $direction, string $message): void
    {
        $this->traffic = new Traffic($direction, $message);
        $this->notify();
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
