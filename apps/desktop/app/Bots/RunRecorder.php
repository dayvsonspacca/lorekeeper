<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Session\Observer;
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
                $event instanceof LeveledUpEvent => $this->leveledUp($event),
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

    public function working(?BotTask $task): void
    {
        $this->task = $task;
    }

    #[Override]
    public function progressed(Progress $progress): void
    {
        $this->task?->update(['progress_done' => $progress->done, 'progress_total' => $progress->total]);
    }

    private function leveledUp(LeveledUpEvent $event): void
    {
        $this->run->account->update(['level' => $event->level->value]);
        $this->task?->update(['level_to' => $event->level->value]);
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
