<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunEvent;
use Lorekeeper\Apps\Wiki\Models\BotTask;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Message\XmlMessage;

final class RunRecorder
{
    private ?Position $position = null;

    private ?BotTask $task = null;

    private bool $idling = false;

    public function __construct(
        private readonly BotRun $run,
    ) {}

    public function heard(Message $message): void
    {
        $recognized = Events::recognize($message);

        if ($recognized === []) {
            $this->event(BotRunEvent::UNRECOGNIZED, self::command($message), $message->raw);

            return;
        }

        foreach ($recognized as $event) {
            if ($event instanceof MalformedEventException) {
                $this->event('Malformed', $event->getMessage(), $message->raw);
                $this->log("Could not read a message: {$event->getMessage()}", LogLevel::Error);

                continue;
            }

            if ($event instanceof MovedToMapEvent) {
                $this->at(new Position($event->map, $event->areaId));
            }

            $this->event(EventSummary::name($event), EventSummary::of($event));
        }
    }

    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        if ($instruction instanceof Pause) {
            if ($instruction->duration->getTotalSeconds() < 60) {
                return;
            }

            if (!$this->idling) {
                $this->idling = true;
                $this->log($this->position === null ? 'Idling' : "Idling in {$this->position->map->value}");
            }

            return;
        }

        $this->idling = false;
        [$level, $message] = StepLog::describe($instruction, $answer);
        $this->log($message, $level);
    }

    public function at(Position $position): void
    {
        $this->position = $position;
        $this->run->update(['map' => $position->map->value]);
    }

    public function position(): ?Position
    {
        return $this->position;
    }

    public function working(?BotTask $task): void
    {
        $this->task = $task;
        $this->idling = false;
    }

    public function log(string $message, LogLevel $level = LogLevel::Info): void
    {
        $this->run->log($message, $level, $this->task);
    }

    public static function command(Message $message): string
    {
        return match (true) {
            $message instanceof JsonMessage => (string) ($message->data['cmd'] ?? 'json'),
            $message instanceof DelimitedMessage => $message->data[1] ?? $message->data[0],
            $message instanceof XmlMessage => self::xmlAction($message),
            default => 'message',
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
