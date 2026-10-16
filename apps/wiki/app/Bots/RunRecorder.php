<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunEvent;
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
    private ?string $map = null;

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
                $this->run->log("Could not read a message: {$event->getMessage()}", LogLevel::Error);

                continue;
            }

            if ($event instanceof MovedToMapEvent) {
                $this->map = $event->map->value;
            }

            $this->event(EventSummary::name($event), EventSummary::of($event));
        }
    }

    public function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        if ($instruction instanceof Pause) {
            if (!$this->idling) {
                $this->idling = true;
                $this->run->log($this->map === null ? 'Idling' : "Idling in {$this->map}");
            }

            return;
        }

        $this->idling = false;
        [$level, $message] = StepLog::describe($instruction, $answer);
        $this->run->log($message, $level);
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
