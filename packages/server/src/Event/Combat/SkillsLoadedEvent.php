<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\DateTime\Duration;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class SkillsLoadedEvent implements Event
{
    /** @param list<Skill> $skills */
    public function __construct(
        public array $skills,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'sAct') {
            return Option\none();
        }

        try {
            $active = Type\shape([
                'actions' => Type\shape([
                    'active' => Type\vec(Type\shape([
                        'ref' => Type\non_empty_string(),
                        'tgt' => Type\string(),
                        'mp' => Type\uint(),
                        'cd' => Type\uint(),
                        'isOK' => Type\optional(Type\bool()),
                    ], allowUnknownFields: true)),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true)->coerce($message->data)['actions']['active'];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'skills are missing or invalid', $exception);
        }

        return Option\some(
            new static(array_map(
                static fn(array $skill): Skill => new Skill(
                    $skill['ref'],
                    $skill['tgt'] === 'h',
                    new Mana($skill['mp']),
                    Duration::milliseconds($skill['cd']),
                    $skill['isOK'] ?? false,
                ),
                $active,
            )),
        );
    }
}
