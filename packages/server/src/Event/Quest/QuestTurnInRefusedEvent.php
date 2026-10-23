<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Quest\Helper\QuestAnswer;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

/**
 * The server refused to complete a quest for our player, and said why.
 */
final readonly class QuestTurnInRefusedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public QuestId $quest,
        public QuestName $name,
        public string $reason,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        return QuestAnswer::read(
            $message,
            static::class,
        )->filter(static fn(QuestAnswer $answer): bool => !$answer->completed)->map(
            static fn(QuestAnswer $answer): static => new static($answer->quest, $answer->name, $answer->reason),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "Could not complete {$this->name->value}: {$this->reason}";
    }
}
