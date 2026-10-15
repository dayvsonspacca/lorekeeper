<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Closure;
use Lorekeeper\Miner\Exception\PeerClosedException;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Protocol\Event\Event;
use Lorekeeper\Protocol\Event\FactEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\Message;
use ReflectionClass;

final class Listener
{
    private int $messages = 0;

    private int $events = 0;

    private int $facts = 0;

    /**
     * @param Closure(string): void $log
     * @param null|Closure(Message): void $unrecognized
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly Recorder $recorder,
        private readonly Session $session,
        private readonly Closure $log,
        private readonly ?Closure $unrecognized = null,
    ) {}

    public function listen(): void
    {
        while (true) {
            try {
                $message = $this->connection->receive();
            } catch (PeerClosedException $exception) {
                if (!$this->connection->closedHere()) {
                    ($this->log)("! {$exception->getMessage()}");
                }

                return;
            }

            $this->messages++;
            $message->apply($this->handle(...));
        }
    }

    /** @return array{messages: int, events: int, facts: int} */
    public function stats(): array
    {
        return ['messages' => $this->messages, 'events' => $this->events, 'facts' => $this->facts];
    }

    private function handle(Message $message): void
    {
        foreach (KnownEvents::recognizers() as $recognize) {
            try {
                $event = $recognize($message);
            } catch (MalformedEventException $exception) {
                ($this->log)("! {$exception->getMessage()}");

                continue;
            }

            if ($event->isNone()) {
                continue;
            }

            $this->observe($event->unwrap());

            return;
        }

        if ($this->unrecognized !== null) {
            ($this->unrecognized)($message);
        }
    }

    private function observe(Event $event): void
    {
        $this->events++;
        $this->session->observe($event);
        $name = new ReflectionClass($event)->getShortName();

        if (!$event instanceof FactEvent) {
            ($this->log)("< {$name}");

            return;
        }

        $facts = $event->facts();
        $this->recorder->record($facts, Source::Socket);
        $this->facts += count($facts);

        ($this->log)("< {$name}: " . count($facts) . ' facts');
    }
}
