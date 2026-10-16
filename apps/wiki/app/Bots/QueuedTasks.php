<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use InvalidArgumentException;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotTask;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Session\Job;
use Lorekeeper\Client\Bot\Session\Jobs;
use Override;

/**
 * Hands a session the pending tasks of its run, oldest first.
 */
final readonly class QueuedTasks implements Jobs
{
    public function __construct(
        private BotRun $run,
    ) {}

    #[Override]
    public function next(): ?Job
    {
        while (($task = $this->run->nextTask()) !== null) {
            try {
                return new Job($task->id, $task->script->task($task->parameters));
            } catch (InvalidArgumentException $invalid) {
                $task->finish(TaskStatus::Failed, $invalid->getMessage());
                $this->run->log("Task #{$task->id} failed: {$invalid->getMessage()}", LogLevel::Error, $task);
            }
        }

        return null;
    }

    #[Override]
    public function cancelled(Job $job): bool
    {
        $task = $this->run->tasks()->find($job->id);

        return !$task instanceof BotTask || $task->status === TaskStatus::Cancelling;
    }
}
