<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Closure;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Exception\PeerClosedException;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Message\Message;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\NullCancellationToken;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final readonly class Client
{
    /**
     * @param null|Closure(Message): void $heard
     * @param null|Closure(Instruction, null|Event|TimedOut): void $stepped
     */
    public function __construct(
        private Connection $connection,
        private ?Closure $heard = null,
        private ?Closure $stepped = null,
    ) {}

    /**
     * @template TResult
     *
     * @param Script<TResult> $script
     *
     * @return TResult|Disconnected|Stopped
     */
    public function run(Script $script, CancellationTokenInterface $stop = new NullCancellationToken()): mixed
    {
        $steps = $script->run();

        try {
            while ($steps->valid()) {
                $stop->throwIfCancelled();
                $instruction = $steps->current();
                $answer = $this->execute($instruction, $stop);

                if ($this->stepped !== null) {
                    ($this->stepped)($instruction, $answer);
                }

                $steps->send($answer);
            }
        } catch (PeerClosedException $closed) {
            return new Disconnected($closed->getMessage());
        } catch (CancelledException $cancelled) {
            if (!$stop->isCancelled()) {
                throw $cancelled;
            }

            return new Stopped();
        }

        return $steps->getReturn();
    }

    /**
     * @throws PeerClosedException
     *
     * @mago-expect lint:literal-named-argument
     */
    public function send(Command $command): void
    {
        $this->connection->send($command);

        if ($this->stepped !== null) {
            ($this->stepped)(new Send($command), null);
        }
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

            return $instruction->await === null ? null : $this->await($instruction->await, $stop);
        }

        if ($instruction instanceof Await) {
            return $this->await($instruction, $stop);
        }

        return null;
    }

    /** @throws PeerClosedException */
    private function await(Await $await, CancellationTokenInterface $stop): Event|TimedOut
    {
        $deadline = Timestamp::monotonic()->plus($await->timeout);

        while (true) {
            $left = $deadline->since(Timestamp::monotonic());
            if (!$left->isPositive()) {
                return new TimedOut($await);
            }

            try {
                $message = $this->connection->receive(
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

            $this->hear($message->unwrap());

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

    private function hear(Message $message): void
    {
        if ($this->heard !== null) {
            ($this->heard)($message);
        }
    }
}
