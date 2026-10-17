<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Log\StepLog;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Message\Message;
use Override;

final class Narrator implements Observer
{
    private bool $idling = false;

    private ?Position $position = null;

    public function __construct(
        private readonly Observer $listener,
    ) {}

    #[Override]
    public function said(string $message, LogLevel $level): void
    {
        $this->listener->said($message, $level);
    }

    #[Override]
    public function heard(Message $message, array $events): void
    {
        $this->listener->heard($message, $events);
    }

    #[Override]
    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        $this->listener->stepped($instruction, $answer);

        if ($instruction instanceof Progress || $instruction instanceof Await && $answer instanceof TimedOut) {
            return;
        }

        if ($instruction instanceof Pause) {
            $this->paused($instruction);

            return;
        }

        $this->idling = false;
        [$level, $message] = StepLog::describe($instruction, $answer);
        $this->listener->said($message, $level);
    }

    #[Override]
    public function moved(Position $position): void
    {
        $this->position = $position;
        $this->listener->moved($position);
    }

    #[Override]
    public function progressed(Progress $progress): void
    {
        $this->listener->progressed($progress);
    }

    private function paused(Pause $pause): void
    {
        if ($pause->duration->getTotalSeconds() < 60 || $this->idling) {
            return;
        }

        $this->idling = true;
        $this->listener->said(
            $this->position === null ? 'Idling' : "Idling in {$this->position->map->value}",
            LogLevel::Info,
        );
    }
}
