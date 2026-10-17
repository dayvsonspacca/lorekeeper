<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunEvent;
use Lorekeeper\Apps\Wiki\Models\Player;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Session\Observer;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\RoomJoinedEvent;
use Lorekeeper\Server\Event\Player\PlayerEnteredEvent;
use Lorekeeper\Server\Event\Player\PlayerLeftEvent;
use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Message\XmlMessage;
use Override;

final class RunRecorder implements Observer
{
    public function __construct(
        private readonly BotRun $run,
    ) {}

    #[Override]
    public function said(string $message, LogLevel $level): void
    {
        $this->run->log($message, $level);
    }

    #[Override]
    public function heard(Message $message, array $events): void
    {
        if ($events === []) {
            $this->event(BotRunEvent::UNRECOGNIZED, self::command($message), $message->raw);

            return;
        }

        foreach ($events as $event) {
            if ($event instanceof MalformedEventException) {
                $this->event('Malformed', $event->getMessage(), $message->raw);
                $this->said("Could not read a message: {$event->getMessage()}", LogLevel::Error);

                continue;
            }

            foreach (self::players($event) as $player) {
                Player::saw($player);
            }

            $this->event($event->name(), $event->summary());
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
    public function progressed(Progress $progress): void {}

    public static function command(Message $message): string
    {
        return match (true) {
            $message instanceof JsonMessage => (string) ($message->data['cmd'] ?? 'json'),
            $message instanceof DelimitedMessage => $message->data[1] ?? $message->data[0],
            $message instanceof XmlMessage => self::xmlAction($message),
            default => 'message',
        };
    }

    /** @return list<PlayerName> */
    private static function players(Event $event): array
    {
        return match (true) {
            $event instanceof MovedToMapEvent, $event instanceof RoomJoinedEvent => $event->players,
            $event instanceof PlayerEnteredEvent,
            $event instanceof PlayerLeftEvent,
            $event instanceof PlayerUpdatedEvent,
                => [$event->name],
            default => [],
        };
    }

    private static function xmlAction(XmlMessage $message): string
    {
        $body = $message->dom->getElementsByTagName('body')->item(0);
        $action = $body?->attributes?->getNamedItem('action')?->nodeValue;

        return $action ?? $message->dom->documentElement->nodeName ?? 'xml';
    }

    private function event(string $name, string $summary, ?string $raw = null): void
    {
        $this->run->events()->create(['name' => $name, 'summary' => $summary, 'raw' => $raw]);
    }
}
