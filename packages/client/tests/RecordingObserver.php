<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Observer;
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

    /** @var list<string> */
    public array $messages = [];

    /** @var list<array{class-string<Instruction>, null|class-string}> */
    public array $steps = [];

    #[Override]
    public function said(string $message, LogLevel $level): void
    {
        $this->lines[] = "{$level->value}: {$message}";
    }

    #[Override]
    public function heard(Message $message, array $events): void
    {
        $this->messages[] = $message->raw;
    }

    #[Override]
    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        $this->steps[] = [$instruction::class, $answer === null ? null : $answer::class];
    }

    #[Override]
    public function moved(Position $position): void
    {
        $this->updates[] = "moved {$position->map->value} {$position->area->value}";
    }

    #[Override]
    public function progressed(Progress $progress): void
    {
        $this->updates[] = "progressed {$progress->done}/{$progress->total}";
    }
}
