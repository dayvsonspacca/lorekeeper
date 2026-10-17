<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Session\Job;
use Lorekeeper\Client\Bot\Session\Observer;
use Lorekeeper\Client\Bot\Session\Outcome;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Message\Message;
use Override;

/**
 * Writes what a session does into its run: the log, where the bot is, and how its tasks go.
 */
final class RunRecorder implements Observer
{
    private ?BotTask $task = null;

    public function __construct(
        private readonly BotRun $run,
    ) {}

    #[Override]
    public function said(string $message, LogLevel $level): void
    {
        $this->run->log($message, $level, $this->task);
    }

    #[Override]
    public function heard(Message $message, array $events): void {}

    #[Override]
    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void {}

    #[Override]
    public function moved(Position $position): void
    {
        $this->run->update(['map' => $position->map->value]);
    }

    #[Override]
    public function started(Job $job): void
    {
        $this->task = $this->run->tasks()->find($job->id);
        $this->task?->update(['status' => TaskStatus::Running, 'started_at' => now()]);
    }

    #[Override]
    public function progressed(Job $job, Progress $progress): void
    {
        $this->task?->update(['progress_done' => $progress->done, 'progress_total' => $progress->total]);
    }

    #[Override]
    public function finished(Job $job, Outcome $outcome): void
    {
        $this->task?->finish(TaskStatus::from($outcome->status->value), $outcome->summary);
        $this->task = null;
    }
}
