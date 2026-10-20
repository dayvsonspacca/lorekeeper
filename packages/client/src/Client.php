<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Closure;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Inventory\LoadPlayerInventoryCommand;
use Lorekeeper\Client\Command\Map\JoinInitialMapCommand;
use Lorekeeper\Client\Command\Session\LoadPlayerDataCommand;
use Lorekeeper\Client\Command\Session\LoginCommand;
use Lorekeeper\Client\Command\Session\PolicyFileRequestCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Client\Session\Entrance;
use Lorekeeper\Client\Session\GameLogin;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Lorekeeper\Server\Message\Message;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use RuntimeException;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 * @mago-expect lint:too-many-methods
 */
final class Client
{
    /** @var list<Closure(Event): void> */
    private array $subscribers = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly Character $me,
    ) {}

    /** @throws CancelledException */
    public static function enter(
        Connection $connection,
        GameLogin $login,
        CancellationTokenInterface $stop = new NullCancellationToken(),
    ): self|LoginFailedEvent|TimedOut|Disconnected|Stopped {
        $entrance = new Entrance($login->player);
        $hear = static function (Message $message) use ($entrance): void {
            foreach (Events::recognize($message) as $event) {
                if (!$event instanceof Event) {
                    continue;
                }

                $entrance->hear($event);
            }
        };

        try {
            $connection->send(new PolicyFileRequestCommand());
            $policy = self::receive($connection, self::awaiting(ConnectionEstablishedEvent::class), $stop, $hear);
            if ($policy instanceof TimedOut) {
                return $policy;
            }

            $connection->send(new LoginCommand($login->player, $login->token, $login->version));
            /** @var LoginSucceededEvent|LoginFailedEvent|TimedOut $logged */
            $logged = self::receive(
                $connection,
                self::awaiting(LoginSucceededEvent::class, LoginFailedEvent::class),
                $stop,
                $hear,
            );
            if (!$logged instanceof LoginSucceededEvent) {
                return $logged;
            }

            $connection->send(new JoinInitialMapCommand());
            /** @var MovedToMapEvent|TimedOut $arrival */
            $arrival = self::receive($connection, self::awaiting(MovedToMapEvent::class), $stop, $hear);
            if ($arrival instanceof TimedOut) {
                return $arrival;
            }

            self::askForThePlayer($connection, $arrival, $logged);
            $missing = $entrance->missing();
            while ($missing !== []) {
                $heard = self::receive($connection, new Await($missing, Duration::seconds(15)), $stop, $hear);
                if ($heard instanceof TimedOut) {
                    return $heard;
                }

                $missing = $entrance->missing();
            }

            return new self($connection, $entrance->character());
        } catch (PeerClosedException $closed) {
            return new Disconnected($closed->getMessage());
        } catch (CancelledException $cancelled) {
            if (!$stop->isCancelled()) {
                throw $cancelled;
            }

            return new Stopped();
        }
    }

    /** @throws PeerClosedException */
    private static function askForThePlayer(
        Connection $connection,
        MovedToMapEvent $arrival,
        LoginSucceededEvent $logged,
    ): void {
        $connection->send(new LoadPlayerInventoryCommand($arrival->areaId, $logged->socketId));
        $connection->send(new LoadPlayerDataCommand($arrival->areaId, $logged->socketId));
    }

    /** @param Closure(Event): void $subscriber */
    public function subscribe(Closure $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    public function character(): Character
    {
        return $this->me;
    }

    /**
     * @template TResult
     *
     * @param Script<TResult> $script
     *
     * @return TResult|Disconnected|Stopped
     */
    public function run(Script $script, CancellationTokenInterface $stop = new NullCancellationToken()): mixed
    {
        $steps = $script->run($this->me);

        try {
            while ($steps->valid()) {
                $stop->throwIfCancelled();
                $instruction = $steps->current();
                $answer = $this->execute($instruction, $stop);

                $steps->send($answer);
            }
        } catch (PeerClosedException $closed) {
            return new Disconnected($closed->getMessage());
        } catch (CancelledException $cancelled) {
            if (!$stop->isCancelled()) {
                throw $cancelled;
            }

            return new Stopped();
        } catch (RuntimeException $broke) {
            return new Failed($broke->getMessage());
        }

        return $steps->getReturn();
    }

    /** @throws PeerClosedException */
    public function send(Command $command): void
    {
        $this->connection->send($command);
    }

    public function wait(
        Duration $duration,
        CancellationTokenInterface $stop = new NullCancellationToken(),
    ): null|Disconnected|Stopped {
        try {
            $this->listen(new Pause($duration), $stop);
        } catch (PeerClosedException $closed) {
            return new Disconnected($closed->getMessage());
        } catch (CancelledException $cancelled) {
            if (!$stop->isCancelled()) {
                throw $cancelled;
            }

            return new Stopped();
        }

        return null;
    }

    /** @throws PeerClosedException */
    private function execute(Instruction $instruction, CancellationTokenInterface $stop): null|Event|TimedOut
    {
        if ($instruction instanceof Pause) {
            $this->listen($instruction, $stop);

            return null;
        }

        if ($instruction instanceof Send) {
            $this->connection->send($instruction->command);

            return (
                $instruction->await === null
                    ? null
                    : self::receive($this->connection, $instruction->await, $stop, $this->hear(...))
            );
        }

        if ($instruction instanceof Await) {
            return self::receive($this->connection, $instruction, $stop, $this->hear(...));
        }

        return null;
    }

    /**
     * @param Closure(Message): void $hear
     *
     * @throws PeerClosedException
     * @throws CancelledException
     */
    private static function receive(
        Connection $connection,
        Await $await,
        CancellationTokenInterface $stop,
        Closure $hear,
    ): Event|TimedOut {
        $deadline = Timestamp::monotonic()->plus($await->timeout);

        while (true) {
            $left = $deadline->since(Timestamp::monotonic());
            if (!$left->isPositive()) {
                return new TimedOut($await);
            }

            try {
                $message = $connection->receive(
                    new Async\LinkedCancellationToken(new Async\TimeoutCancellationToken($left), $stop),
                );
            } catch (CancelledException $cancelled) {
                if ($stop->isCancelled()) {
                    throw $cancelled;
                }

                return new TimedOut($await);
            }

            if ($message->isNone()) {
                continue;
            }

            $hear($message->unwrap());

            foreach ($await->events as $event) {
                /** @mago-expect analysis:possibly-static-access-on-interface */
                $heard = $event::from($message->unwrap());
                if ($heard->isSome()) {
                    return $heard->unwrap();
                }
            }
        }
    }

    /** @throws PeerClosedException */
    private function listen(Pause $pause, CancellationTokenInterface $stop): void
    {
        $deadline = Timestamp::monotonic()->plus($pause->duration);

        while (true) {
            $left = $deadline->since(Timestamp::monotonic());
            if (!$left->isPositive()) {
                return;
            }

            try {
                $message = $this->connection->receive(
                    new Async\LinkedCancellationToken(new Async\TimeoutCancellationToken($left), $stop),
                );
            } catch (CancelledException $cancelled) {
                if ($stop->isCancelled()) {
                    throw $cancelled;
                }

                return;
            }

            $message->apply($this->hear(...));
        }
    }

    public function close(): void
    {
        $this->connection->close();
    }

    /**
     * @param class-string<Event> $event
     * @param class-string<Event> ...$more
     */
    private static function awaiting(string $event, string ...$more): Await
    {
        return new Await([$event, ...array_values($more)], Duration::seconds(15));
    }

    private function hear(Message $message): void
    {
        $events = Events::recognize($message);

        foreach ($events as $event) {
            if (!$event instanceof Event) {
                continue;
            }

            $this->me->hear($event);
            foreach ($this->subscribers as $subscriber) {
                $subscriber($event);
            }
        }
    }
}
