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
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\PlayersPresentEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\Message\Message;
use Override;

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
    public function heard(Message $message, array $events): void
    {
        foreach ($events as $event) {
            match (true) {
                $event instanceof MonsterRewardedEvent => $this->task?->incrementEach([
                    'kills' => 1,
                    'experience' => $event->experience->value,
                    'gold' => $event->gold->value,
                ]),
                $event instanceof PlayerDiedEvent => $this->task?->increment('deaths'),
                $event instanceof LeveledUpEvent => $this->run->account->update(['level' => $event->level->value]),
                $event instanceof PlayersPresentEvent => $this->sawLevel($event),
                default => null,
            };
        }
    }

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
        $this->task?->update([
            'status' => TaskStatus::Running,
            'started_at' => now(),
            'level_from' => $this->run->account->level,
        ]);
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

    private function sawLevel(PlayersPresentEvent $event): void
    {
        foreach ($event->players as $player) {
            if ($player->level === null || strcasecmp($player->name->value, $this->run->account->name) !== 0) {
                continue;
            }

            $this->run->account->update(['level' => $player->level->value]);
        }
    }
}
