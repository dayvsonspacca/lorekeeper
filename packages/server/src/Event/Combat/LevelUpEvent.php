<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class LevelUpEvent implements Event
{
    public function __construct(
        public PlayerLevel $level,
        public Experience $toNextLevel,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'levelUp') {
            return Option\none();
        }

        try {
            $data = Type\shape([
                'intLevel' => Type\int_range(min: 1, max: 100),
                'intExpToLevel' => Type\uint(),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'level is missing or invalid', $exception);
        }

        return Option\some(new static(new PlayerLevel($data['intLevel']), new Experience($data['intExpToLevel'])));
    }
}
