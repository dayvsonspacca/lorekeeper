<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Session;

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

final class RecordingObserver implements Observer
{
    /** @var list<string> */
    public array $lines = [];

    /** @var list<string> */
    public array $updates = [];

    public int $heard = 0;

    public int $steps = 0;

    #[Override]
    public function said(string $message, LogLevel $level): void
    {
        $this->lines[] = "{$level->value}: {$message}";
    }

    #[Override]
    public function heard(Message $message, array $events): void
    {
        $this->heard++;
    }

    #[Override]
    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        $this->steps++;
    }

    #[Override]
    public function moved(Position $position): void
    {
        $this->updates[] = "moved {$position->map->value} {$position->area->value}";
    }

    #[Override]
    public function started(Job $job): void
    {
        $this->updates[] = "started {$job->id}";
    }

    #[Override]
    public function progressed(Job $job, Progress $progress): void
    {
        $this->updates[] = "progressed {$job->id} {$progress->done}/{$progress->total}";
    }

    #[Override]
    public function finished(Job $job, Outcome $outcome): void
    {
        $this->updates[] = "finished {$job->id} {$outcome->status->value}: {$outcome->summary}";
    }
}
