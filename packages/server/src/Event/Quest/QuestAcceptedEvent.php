<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class QuestAcceptedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public QuestId $quest,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'acceptQuest') {
            return Option\none();
        }

        try {
            $answer = Type\shape([
                'QuestID' => Type\positive_int(),
                'bSuccess' => Type\int(),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'accepted quest is invalid', $exception);
        }

        return $answer['bSuccess'] === 1 ? Option\some(new static(new QuestId($answer['QuestID']))) : Option\none();
    }

    #[Override]
    public function summary(): string
    {
        return "Accepted quest {$this->quest->value}";
    }
}
