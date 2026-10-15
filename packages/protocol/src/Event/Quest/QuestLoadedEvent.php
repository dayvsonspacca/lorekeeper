<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Quest;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Protocol\Event\FactEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class QuestLoadedEvent implements FactEvent
{
    /** @param list<Fact<Subject, object>> $facts */
    public function __construct(
        public array $facts,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'getQuests') {
            return Option\none();
        }

        try {
            $quests = Type\dict(Type\array_key(), QuestFacts::type())->coerce($message->data['quests'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'quests are missing or invalid', $exception);
        }

        $facts = [];
        foreach ($quests as $quest) {
            $facts = [...$facts, ...QuestFacts::of($quest)];
        }

        return Option\some(new static($facts));
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
