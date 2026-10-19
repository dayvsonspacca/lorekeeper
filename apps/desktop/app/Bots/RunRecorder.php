<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Bots\Scene\Witness;
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

    private readonly Witness $witness;

    public function __construct(
        private readonly BotRun $run,
    ) {
        $this->witness = new Witness();
    }

    public function working(?BotTask $task): void
    {
        $this->task = $task;
    }

    public function heard(Event $event): void
    {
        match (true) {
            $event instanceof MonsterRewardedEvent => $this->rewarded($event),
            $event instanceof PlayerDiedEvent => $this->died(),
            $event instanceof ResurrectedEvent => $this->log('Back on their feet'),
            $event instanceof MovedToMapEvent => $this->log("Arrived in {$event->map->name->value}"),
            $event instanceof LeveledUpEvent => $this->log("Reached level {$event->level->value}", LogLevel::Success),
            default => null,
        };

        $this->keep($this->witness->heard($event, now()->getTimestamp()));
    }

    #[Override]
    public function update(SubjectInterface $subject): void
    {
        if (!$subject instanceof Character) {
            return;
        }

        $map = $subject->location()->map->name->value;
        $level = $subject->player()->level->value;

        if ($this->run->map !== $map) {
            $this->run->update(['map' => $map]);
        }

        if ($this->run->account->level !== $level) {
            $this->run->account->update(['level' => $level]);
        }

        $this->reached($level);
        $this->keep($this->witness->saw($subject, now()->getTimestamp()));
    }

    private function keep(?Scene $scene): void
    {
        if ($scene !== null) {
            $this->run->update(['scene' => $scene->toArray()]);
        }
    }

    private function rewarded(MonsterRewardedEvent $event): void
    {
        $this->task?->incrementEach([
            'kills' => 1,
            'experience' => $event->experience->value,
            'gold' => $event->gold->value,
        ]);
        $this->log(sprintf(
            'Defeated %s: +%s XP, +%s gold',
            $this->witness->monsterName($event->monster) ?? 'a monster',
            number_format($event->experience->value),
            number_format($event->gold->value),
        ));
    }

    private function died(): void
    {
        $this->task?->increment('deaths');
        $this->log('Fell in battle', LogLevel::Warning);
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

    private function log(string $message, LogLevel $level = LogLevel::Info): void
    {
        $this->run->log($message, $level, $this->task);
    }
}
