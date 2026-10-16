<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

use Closure;
use Psl\Async;
use Psl\Async\Awaitable;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;

/**
 * Cancels a token once a condition holds, checking it every so often in the background. Useful when the wish to
 * stop lives somewhere the bot cannot be signalled from, like a database row.
 */
final class Watch
{
    private readonly SignalCancellationToken $token;

    private readonly SignalCancellationToken $released;

    /** @var Awaitable<void> */
    private readonly Awaitable $watcher;

    /** @param Closure(): bool $condition */
    private function __construct(Closure $condition, Duration $every)
    {
        $this->token = new SignalCancellationToken();
        $this->released = new SignalCancellationToken();

        $token = $this->token;
        $released = $this->released;
        $this->watcher = Async\run(static function () use ($condition, $every, $token, $released): void {
            while (!$released->isCancelled()) {
                try {
                    Async\sleep($every, $released);
                } catch (CancelledException) {
                    return;
                }

                if ($condition()) {
                    $token->cancel();

                    return;
                }
            }
        });
    }

    /** @param Closure(): bool $condition */
    public static function until(Closure $condition, ?Duration $every = null): self
    {
        return new self($condition, $every ?? Duration::seconds(1));
    }

    public function token(): CancellationTokenInterface
    {
        return $this->token;
    }

    public function release(): void
    {
        if (!$this->released->isCancelled()) {
            $this->released->cancel();
        }

        $this->watcher->await();
    }
}
