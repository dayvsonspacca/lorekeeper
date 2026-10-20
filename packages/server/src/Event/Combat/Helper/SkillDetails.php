<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat\Helper;

use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageMultiplier;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageSource;
use Lorekeeper\Core\CharacterClass\Skill\Damage\SkillDamage;
use Lorekeeper\Core\CharacterClass\Skill\SkillDescription;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Psl\Option;

final readonly class SkillDetails
{
    /** @return Option\Option<SkillDescription> */
    public static function description(string $description): Option\Option
    {
        return $description === '' ? Option\none() : Option\some(new SkillDescription($description));
    }

    /** @return Option\Option<SkillDamage> */
    public static function damage(int|float $multiplier, string $source): Option\Option
    {
        $from = match (true) {
            str_starts_with($source, 'AP') => DamageSource::Physical,
            str_starts_with($source, 'SP') => DamageSource::Magical,
            default => null,
        };
        if ($from === null || $multiplier <= 0) {
            return Option\none();
        }

        return Option\some(new SkillDamage(new DamageMultiplier((float) $multiplier), $from));
    }

    public static function slot(string $ref): ?SkillSlot
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
    public static function target(string $target): SkillTarget
    {
        return match ($target) {
            'h' => SkillTarget::Enemy,
            's' => SkillTarget::Self,
            'f' => SkillTarget::Ally,
            default => throw new MalformedEventException(SkillsLoadedEvent::class, "unknown skill target '{$target}'"),
        };
    }
}
