<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Quest\Helper\QuestMarks;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class QuestMarkedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public QuestMark $mark,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'setAchievement') {
            return Option\none();
        }

        try {
            $achievement = Type\shape([
                'field' => Type\string(),
                'index' => Type\uint(),
                'value' => Type\int(),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'achievement is invalid', $exception);
        }

        if ($achievement['value'] !== 1) {
            return Option\none();
        }

        return QuestMarks::of($achievement['field'], $achievement['index'])->map(
            static fn(QuestMark $mark): static => new static($mark),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "Marked {$this->mark->period->name} quest {$this->mark->group}:{$this->mark->index}";
    }
}
