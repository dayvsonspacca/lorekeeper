<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\CharacterClass\Passive\PassiveDescription;
use Lorekeeper\Core\CharacterClass\Passive\PassiveName;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillName;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Combat\Helper\SkillDetails;
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

    /**
     * @param list<PlayerSkill> $skills
     * @param list<ClassPassive> $passives
     */
    public function __construct(
        public array $skills,
        public array $passives,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'sAct') {
            return Option\none();
        }

        try {
            $actions = Type\shape([
                'actions' => Type\shape([
                    'active' => Type\vec(Type\shape([
                        'ref' => Type\non_empty_string(),
                        'nam' => Type\non_empty_string(),
                        'desc' => Type\optional(Type\string()),
                        'tgt' => Type\string(),
                        'mp' => Type\int(),
                        'cd' => Type\uint(),
                        'tgtMax' => Type\optional(Type\positive_int()),
                        'damage' => Type\optional(Type\num()),
                        'dsrc' => Type\optional(Type\string()),
                        'isOK' => Type\optional(Type\bool()),
                    ], allowUnknownFields: true)),
                    'passive' => Type\optional(Type\vec(Type\shape([
                        'nam' => Type\non_empty_string(),
                        'desc' => Type\non_empty_string(),
                    ], allowUnknownFields: true))),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true)->coerce($message->data)['actions'];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(
                static::class,
                'skills are missing or invalid: ' . $exception->getMessage(),
                $exception,
            );
        }

        $skills = [];
        foreach ($actions['active'] as $skill) {
            $slot = SkillDetails::slot($skill['ref']);
            if ($slot === null) {
                continue;
            }

            $skills[] = new PlayerSkill(
                new Skill(
                    $slot,
                    new SkillName($skill['nam']),
                    SkillDetails::description($skill['desc'] ?? ''),
                    SkillDetails::target($skill['tgt']),
                    $skill['mp'] < 0 ? new ManaRestore(new Mana(-$skill['mp'])) : new ManaCost(new Mana($skill['mp'])),
                    Duration::milliseconds($skill['cd']),
                    new Quantity($skill['tgtMax'] ?? 1),
                    SkillDetails::damage($skill['damage'] ?? 0, $skill['dsrc'] ?? ''),
                ),
                $skill['isOK'] ?? false,
            );
        }

        $passives = array_map(
            static fn(array $passive): ClassPassive => new ClassPassive(
                new PassiveName($passive['nam']),
                new PassiveDescription($passive['desc']),
            ),
            $actions['passive'] ?? [],
        );

        return Option\some(new static($skills, $passives));
    }

    #[Override]
    public function summary(): string
    {
        $unlocked = array_filter($this->skills, static fn(PlayerSkill $skill): bool => $skill->unlocked);

        return count($this->skills) . ' skills, ' . count($unlocked) . ' unlocked';
    }
}
