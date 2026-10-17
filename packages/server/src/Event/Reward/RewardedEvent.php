<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Reward;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

abstract readonly class RewardedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public Gold $gold,
        public Experience $experience,
        public ClassPoints $classPoints,
    ) {}

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws MalformedEventException
     *
     * @return Option\Option<static>
     */
    abstract protected static function read(
        array $data,
        Gold $gold,
        Experience $experience,
        ClassPoints $classPoints,
    ): Option\Option;

    /** @return Option\Option<static> */
    #[Override]
    final public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'addGoldExp') {
            return Option\none();
        }

        try {
            $reward = Type\shape([
                'intGold' => Type\optional(Type\uint()),
                'intExp' => Type\optional(Type\uint()),
                'iCP' => Type\optional(Type\uint()),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'reward is invalid', $exception);
        }

        return static::read(
            $message->data,
            new Gold($reward['intGold'] ?? 0),
            new Experience($reward['intExp'] ?? 0),
            new ClassPoints($reward['iCP'] ?? 0),
        );
    }
}
