<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\CharacterClass;

use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassDescription;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\ManaRegeneration;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Protocol\Event\FactEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use Lorekeeper\Protocol\SocketId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class ClassChangedEvent implements FactEvent
{
    /** @param list<Fact<Subject, object>> $facts */
    public function __construct(
        public SocketId $player,
        public ClassName $className,
        private array $facts,
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
                'sDesc' => Type\optional(Type\string()),
                'aMRM' => Type\optional(Type\vec(Type\string())),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'class is missing or invalid', $exception);
        }

        $class = new ClassName($data['sClassName']);
        $facts = [];
        $category = self::category($data['sClassCat']);
        if ($category !== null) {
            $facts[] = new Fact($class, $category);
        }

        $description = trim($data['sDesc'] ?? '');
        if ($description !== '') {
            $facts[] = new Fact($class, new ClassDescription($description));
        }

        foreach ($data['aMRM'] ?? [] as $mechanic) {
            $mechanic = trim($mechanic);
            if ($mechanic === '') {
                continue;
            }

            $facts[] = new Fact($class, new ManaRegeneration($mechanic));
        }

        return Option\some(new static(new SocketId($data['uid']), $class, $facts));
    }

    private static function category(string $code): ?ClassCategory
    {
        return match (substr($code, offset: 0, length: 1)) {
            'M' => ClassCategory::Melee,
            'C' => ClassCategory::Caster,
            default => null,
        };
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
