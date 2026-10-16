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

/**
 * Follows a session as it plays. Apps implement it to show, store or react to what the bot does.
 */
interface Observer
{
    /** A line telling what the bot does, in plain words. */
    public function said(string $message, LogLevel $level): void;

    /**
     * Every message the server sent, with the events read from it.
     *
     * @param list<Event|MalformedEventException> $events
     */
    public function heard(Message $message, array $events): void;

    /** Every step a script played, with the answer it got. */
    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void;

    public function moved(Position $position): void;

    public function started(Job $job): void;

    public function progressed(Job $job, Progress $progress): void;

    public function finished(Job $job, Outcome $outcome): void;
}
