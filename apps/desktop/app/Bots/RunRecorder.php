<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use LogicException;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Client\Character;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Override;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;

/** @implements ObserverInterface<Character> */
final class RunRecorder implements ObserverInterface
{
    private ?BotTask $task = null;

    public function __construct(
        private readonly BotRun $run,
    ) {}

    public function working(?BotTask $task): void
    {
        $this->task = $task;
    }

    public function heard(Event $event): void
    {
        match (true) {
            $event instanceof MonsterRewardedEvent => $this->rewarded($event),
            $event instanceof PlayerDiedEvent => $this->died($event),
            $event instanceof ResurrectedEvent, $event instanceof MovedToMapEvent => $this->log($event),
            $event instanceof LeveledUpEvent => $this->log($event, LogLevel::Success),
            default => null,
        };
    }

    #[Override]
    public function update(SubjectInterface $subject): void
    {
        if (!$subject instanceof Character) {
            return;
        }

        try {
            $map = $subject->position()->map->value;
            $level = $subject->level()->value;
        } catch (LogicException) {
            return;
        }

        if ($this->run->map !== $map) {
            $this->run->update(['map' => $map]);
        }

        if ($this->run->account->level !== $level) {
            $this->run->account->update(['level' => $level]);
        }

        $this->reached($level);
    }

    private function rewarded(MonsterRewardedEvent $event): void
    {
        $this->task?->incrementEach([
            'kills' => 1,
            'experience' => $event->experience->value,
            'gold' => $event->gold->value,
        ]);
        $this->log($event);
    }

    private function died(PlayerDiedEvent $event): void
    {
        $this->task?->increment('deaths');
        $this->log($event, LogLevel::Warning);
    }

    private function reached(int $level): void
    {
        $task = $this->task;
        if ($task === null || $task->script !== BotScript::LevelUp || $task->level_from === null) {
            return;
        }

        $total = max(1, Parameters::level($task->parameters)->value - $task->level_from);
        $done = max(0, min($total, $level - $task->level_from));
        if ($task->level_to === $level && $task->progress_done === $done && $task->progress_total === $total) {
            return;
        }

        $task->update(['level_to' => $level, 'progress_done' => $done, 'progress_total' => $total]);
    }

    private function log(Event $event, LogLevel $level = LogLevel::Info): void
    {
        $this->run->log($event->summary(), $level, $this->task);
    }
}
