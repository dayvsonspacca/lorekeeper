<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Quest\Helper\QuestGroups;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A daily, weekly or monthly quest was marked as done for our player.
 */
final readonly class QuestMarkedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public QuestGroupId $quest,
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

        return QuestGroups::of($achievement['field'], $achievement['index'])->map(
            static fn(QuestGroupId $quest): static => new static($quest),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "Completed {$this->quest->group->name} quest {$this->quest->number}";
    }
}
