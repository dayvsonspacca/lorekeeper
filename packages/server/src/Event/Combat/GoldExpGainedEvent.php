<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class GoldExpGainedEvent implements Event
{
    public function __construct(
        public Gold $gold,
        public Experience $experience,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'addGoldExp') {
            return Option\none();
        }

        try {
            $data = Type\shape([
                'intGold' => Type\optional(Type\uint()),
                'intExp' => Type\optional(Type\uint()),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'gold or experience is invalid', $exception);
        }

        return Option\some(new static(new Gold($data['intGold'] ?? 0), new Experience($data['intExp'] ?? 0)));
    }
}
