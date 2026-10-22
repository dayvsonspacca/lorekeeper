<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Bots\Scene\Witness;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Client\Character;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Override;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;

/** @implements ObserverInterface<Character> */
final class BotRecorder implements ObserverInterface
{
    private ?bool $fallen = null;

    private readonly Witness $witness;

    private readonly Journal $journal;

    private ?Character $me = null;

    public function __construct(
        private readonly Account $account,
    ) {
        $this->witness = new Witness();
        $this->journal = new Journal();
    }

    public function heard(Event $event): void
    {
        match (true) {
            $event instanceof MonsterRewardedEvent => $this->rewarded($event),
            $event instanceof MovedToMapEvent => $this->log("Arrived in {$event->map->name->value}"),
            $event instanceof LeveledUpEvent => $this->log("Reached level {$event->level->value}", LogLevel::Success),
            default => null,
        };
        foreach ($this->journal->heard($event, $this->me) as [$message, $level]) {
            $this->log($message, $level);
        }

        $this->keep($this->witness->heard($event, now()->getTimestamp()));
    }

    #[Override]
    public function update(SubjectInterface $subject): void
    {
        if (!$subject instanceof Character) {
            return;
        }

        $this->me = $subject;
        $map = $subject->location()->map->name->value;
        $level = $subject->player()->progress->level->value;

        if ($this->account->map !== $map || $this->account->level !== $level) {
            $this->account->update(['map' => $map, 'level' => $level]);
        }

        $this->stood($subject->state());
        $this->keep($this->witness->saw($subject, now()->getTimestamp()));
    }

    private function keep(?Scene $scene): void
    {
        if ($scene !== null) {
            $this->account->update(['scene' => $scene->toArray()]);
        }
    }

    private function rewarded(MonsterRewardedEvent $event): void
    {
        $this->log(sprintf(
            'Defeated %s: +%s XP, +%s gold',
            $this->witness->monsterName($event->monster) ?? 'a monster',
            number_format($event->experience->value),
            number_format($event->gold->value),
        ));
    }

    private function stood(CombatState $state): void
    {
        $fallen = $state === CombatState::Dead;
        $was = $this->fallen;
        $this->fallen = $fallen;
        if ($was === null || $was === $fallen) {
            return;
        }

        if (!$fallen) {
            $this->log('Back on their feet');

            return;
        }

        $this->log('Fell in battle', LogLevel::Warning);
    }

    private function log(string $message, LogLevel $level = LogLevel::Info): void
    {
        $this->account->log($message, $level);
    }
}
