<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;

interface Observer
{
    public function said(string $message, LogLevel $level): void;

    /** @param list<Event|MalformedEventException> $events */
    public function heard(Message $message, array $events): void;

    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void;

    public function moved(Position $position): void;

    public function started(Job $job): void;

    public function progressed(Job $job, Progress $progress): void;

    public function finished(Job $job, Outcome $outcome): void;
}
