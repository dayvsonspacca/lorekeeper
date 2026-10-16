<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Closure;
use Lorekeeper\Client\Exception\PeerClosedException;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Message\Message;
use Psl\Async;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Timestamp;

final readonly class Client
{
    /** @param null|Closure(Message): void $unheard */
    public function __construct(
        private Connection $connection,
        private ?Closure $unheard = null,
    ) {}

    /**
     * @template TResult
     *
     * @param Script<TResult> $script
     *
     * @return TResult|Disconnected
     */
    public function run(Script $script): mixed
    {
        $steps = $script->run();

        try {
            while ($steps->valid()) {
                $steps->send($this->execute($steps->current()));
            }
        } catch (PeerClosedException $closed) {
            return new Disconnected($closed->getMessage());
        }

        return $steps->getReturn();
    }

    /** @throws PeerClosedException */
    private function execute(Instruction $instruction): null|Event|TimedOut
    {
        if ($instruction instanceof Pause) {
            Async\sleep($instruction->duration);

            return null;
        }

        if ($instruction instanceof Send) {
            $this->connection->send($instruction->command);

            return $instruction->await === null ? null : $this->await($instruction->await);
        }

        if ($instruction instanceof Await) {
            return $this->await($instruction);
        }

        return null;
    }

    /** @throws PeerClosedException */
    private function await(Await $await): Event|TimedOut
    {
        $deadline = Timestamp::monotonic()->plus($await->timeout);

        while (true) {
            $left = $deadline->since(Timestamp::monotonic());
            if (!$left->isPositive()) {
                return new TimedOut($await);
            }

            try {
                $message = $this->connection->receive(new Async\TimeoutCancellationToken($left));
            } catch (CancelledException) {
                return new TimedOut($await);
            }

            if ($message->isNone()) {
                continue;
            }

            foreach ($await->events as $event) {
                /** @mago-expect analysis:possibly-static-access-on-interface */
                $heard = $event::from($message->unwrap());
                if ($heard->isSome()) {
                    return $heard->unwrap();
                }
            }

            if ($this->unheard !== null) {
                ($this->unheard)($message->unwrap());
            }
        }
    }
}
