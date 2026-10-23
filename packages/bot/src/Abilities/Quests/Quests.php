<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Quests;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Quest\AcceptQuestCommand;
use Lorekeeper\Client\Command\Quest\LoadQuestCommand;
use Lorekeeper\Client\Command\Quest\TurnInQuestCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestTurnInRefusedEvent;
use Psl\DateTime\Duration;

trait Quests
{
    abstract protected function me(): Character;

    abstract protected function send(Command $command): Send;

    /** @return Generator<int, Instruction, null|Event|TimedOut, bool> */
    protected function alreadyDone(QuestId $quest): Generator
    {
        /** @var Event|TimedOut $answer */
        $answer = yield $this->send(new LoadQuestCommand($this->me()->location()->area, $quest))->awaits(
            Duration::seconds(10),
            QuestLoadedEvent::class,
        );
        if (!$answer instanceof QuestLoadedEvent) {
            return false;
        }

        foreach ($answer->quests as $loaded) {
            if ($loaded->id->value !== $quest->value) {
                continue;
            }

            return $this
                ->me()
                ->hero()
                ->progression->quests->completed($loaded);
        }

        return false;
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, bool> */
    protected function accept(QuestId $quest): Generator
    {
        /** @var Event|TimedOut $answer */
        $answer = yield $this->send(new AcceptQuestCommand($this->me()->location()->area, $quest))->awaits(
            Duration::seconds(10),
            QuestAcceptedEvent::class,
        );

        return $answer instanceof QuestAcceptedEvent && $answer->quest->value === $quest->value;
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, ?string> */
    protected function turnIn(QuestId $quest): Generator
    {
        /** @var Event|TimedOut $answer */
        $answer = yield $this->send(new TurnInQuestCommand($this->me()->location()->area, $quest))->awaits(
            Duration::seconds(10),
            QuestCompletedEvent::class,
            QuestTurnInRefusedEvent::class,
        );

        return match (true) {
            $answer instanceof QuestCompletedEvent => null,
            $answer instanceof QuestTurnInRefusedEvent => $answer->reason === '' ? 'no reason given' : $answer->reason,
            default => 'the game did not answer',
        };
    }
}
