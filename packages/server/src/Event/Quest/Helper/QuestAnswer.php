<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest\Helper;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class QuestAnswer
{
    private function __construct(
        public QuestId $quest,
        public QuestName $name,
        public bool $completed,
        public string $reason,
    ) {}

    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return Option\Option<self>
     */
    public static function read(Message $message, string $event): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'ccqr') {
            return Option\none();
        }

        try {
            $answer = Type\shape([
                'bSuccess' => Type\int(),
                'QuestID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'msg' => Type\optional(Type\string()),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'quest answer is invalid', $exception);
        }

        return Option\some(
            new self(
                new QuestId($answer['QuestID']),
                new QuestName($answer['sName']),
                $answer['bSuccess'] === 1,
                $answer['msg'] ?? '',
            ),
        );
    }
}
