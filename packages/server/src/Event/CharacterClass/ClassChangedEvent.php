<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\CharacterClass;

use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassDescription;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\SocketId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A player in the room changed their class.
 */
final readonly class ClassChangedEvent implements Event
{
    use NamedAfterClass;

    /** @param Option\Option<ClassDescription> $description */
    public function __construct(
        public SocketId $player,
        public ClassName $className,
        public ClassCategory $category,
        public ClassPoints $points,
        public Option\Option $description,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'updateClass') {
            return Option\none();
        }

        try {
            $data = Type\shape([
                'uid' => Type\positive_int(),
                'sClassName' => Type\non_empty_string(),
                'sClassCat' => Type\string(),
                'iCP' => Type\uint(),
                'sDesc' => Type\optional(Type\string()),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'class is missing or invalid', $exception);
        }

        $description = trim($data['sDesc'] ?? '');

        return Option\some(
            new static(
                new SocketId($data['uid']),
                new ClassName($data['sClassName']),
                self::category($data['sClassCat']),
                new ClassPoints($data['iCP']),
                $description === '' ? Option\none() : Option\some(new ClassDescription($description)),
            ),
        );
    }

    /** @throws MalformedEventException */
    private static function category(string $code): ClassCategory
    {
        return match (substr($code, offset: 0, length: 1)) {
            'M' => ClassCategory::Melee,
            'C' => ClassCategory::Caster,
            'S' => ClassCategory::Hybrid,
            default => throw new MalformedEventException(static::class, "unknown class category '{$code}'"),
        };
    }

    #[Override]
    public function summary(): string
    {
        return "Socket {$this->player->value} is {$this->className->value} ({$this->category->name}, {$this->points->value} CP)";
    }
}
