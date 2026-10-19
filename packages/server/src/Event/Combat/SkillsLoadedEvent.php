<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
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
    use NamedAfterClass;

    /** @param list<PlayerSkill> $skills */
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
                        'mp' => Type\int(),
                        'cd' => Type\uint(),
                        'tgtMax' => Type\optional(Type\positive_int()),
                        'isOK' => Type\optional(Type\bool()),
                    ], allowUnknownFields: true)),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true)->coerce($message->data)['actions']['active'];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(
                static::class,
                'skills are missing or invalid: ' . $exception->getMessage(),
                $exception,
            );
        }

        $skills = [];
        foreach ($active as $skill) {
            $slot = self::slot($skill['ref']);
            if ($slot === null) {
                continue;
            }

            $skills[] = new PlayerSkill(
                new Skill(
                    $slot,
                    self::target($skill['tgt']),
                    $skill['mp'] < 0 ? new ManaRestore(new Mana(-$skill['mp'])) : new ManaCost(new Mana($skill['mp'])),
                    Duration::milliseconds($skill['cd']),
                    new Quantity($skill['tgtMax'] ?? 1),
                ),
                $skill['isOK'] ?? false,
            );
        }

        return Option\some(new static($skills));
    }

    private static function slot(string $ref): ?SkillSlot
    {
        return match ($ref) {
            'aa' => SkillSlot::AutoAttack,
            'a1' => SkillSlot::First,
            'a2' => SkillSlot::Second,
            'a3' => SkillSlot::Third,
            'a4' => SkillSlot::Fourth,
            'i1' => SkillSlot::Potion,
            default => null,
        };
    }

    /** @throws MalformedEventException */
    private static function target(string $target): SkillTarget
    {
        return match ($target) {
            'h' => SkillTarget::Enemy,
            's' => SkillTarget::Self,
            'f' => SkillTarget::Ally,
            default => throw new MalformedEventException(static::class, "unknown skill target '{$target}'"),
        };
    }

    #[Override]
    public function summary(): string
    {
        $unlocked = array_filter($this->skills, static fn(PlayerSkill $skill): bool => $skill->unlocked);

        return count($this->skills) . ' skills, ' . count($unlocked) . ' unlocked';
    }
}
