<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use InvalidArgumentException;
use Lorekeeper\Core\Attribute\Stat\Boost\DamageBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\DotBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\HealingBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\MagicalBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\PhysicalBoost;
use Lorekeeper\Core\Attribute\Stat\Chance\CriticalChance;
use Lorekeeper\Core\Attribute\Stat\Chance\Evasion;
use Lorekeeper\Core\Attribute\Stat\Chance\HitChance;
use Lorekeeper\Core\Attribute\Stat\CriticalMultiplier;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Stat\HealingIntake;
use Lorekeeper\Core\Attribute\Stat\Power\AttackPower;
use Lorekeeper\Core\Attribute\Stat\Power\SpellPower;
use Lorekeeper\Core\Attribute\Stat\Primary\Dexterity;
use Lorekeeper\Core\Attribute\Stat\Primary\Endurance;
use Lorekeeper\Core\Attribute\Stat\Primary\Intellect;
use Lorekeeper\Core\Attribute\Stat\Primary\Luck;
use Lorekeeper\Core\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Attribute\Stat\Primary\Wisdom;
use Lorekeeper\Core\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\DotResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\MagicalResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\PhysicalResistance;
use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psl\Type\Exception\CoercionException;

final readonly class StatsUpdatedEvent implements Event
{
    use NamedAfterClass;

    private const float BASE_HIT = 0.9;

    /** @param non-empty-list<Stat> $stats */
    public function __construct(
        public array $stats,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'stu') {
            return Option\none();
        }

        try {
            $sent = Type\dict(Type\string(), Type\num())->coerce($message->data['sta'] ?? null);
            $stats = [];
            foreach ($sent as $key => $value) {
                $stat = self::stat($key, $value);
                if ($stat !== null) {
                    $stats[] = $stat;
                }
            }
        } catch (CoercionException|AssertException|InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, 'a stat is missing or out of range', $exception);
        }

        return $stats === [] ? Option\none() : Option\some(new static($stats));
    }

    /**
     * @throws CoercionException
     * @throws InvalidArgumentException
     */
    private static function stat(string $key, int|float $value): ?Stat
    {
        return match ($key) {
            '$STR' => new Strength(Type\uint()->coerce($value)),
            '$INT' => new Intellect(Type\uint()->coerce($value)),
            '$DEX' => new Dexterity(Type\uint()->coerce($value)),
            '$END' => new Endurance(Type\uint()->coerce($value)),
            '$WIS' => new Wisdom(Type\uint()->coerce($value)),
            '$LCK' => new Luck(Type\uint()->coerce($value)),
            '$ap' => new AttackPower(Type\uint()->coerce($value)),
            '$sp' => new SpellPower(Type\uint()->coerce($value)),
            '$thi' => new HitChance(self::BASE_HIT + self::ratio($value)),
            '$tha' => new Haste(self::ratio($value)),
            '$tcr' => new CriticalChance(self::ratio($value)),
            '$scm' => new CriticalMultiplier(self::ratio($value)),
            '$tdo' => new Evasion(self::ratio($value)),
            '$cai' => new DamageResistance(1 - self::ratio($value)),
            '$cpi' => new PhysicalResistance(1 - self::ratio($value)),
            '$cmi' => new MagicalResistance(1 - self::ratio($value)),
            '$cdi' => new DotResistance(1 - self::ratio($value)),
            '$cao' => new DamageBoost(self::ratio($value) - 1),
            '$cpo' => new PhysicalBoost(self::ratio($value) - 1),
            '$cmo' => new MagicalBoost(self::ratio($value) - 1),
            '$cho' => new HealingBoost(self::ratio($value) - 1),
            '$cdo' => new DotBoost(self::ratio($value) - 1),
            '$chi' => new HealingIntake(self::ratio($value)),
            default => null,
        };
    }

    private static function ratio(int|float $value): float
    {
        return (float) $value;
    }

    #[Override]
    public function summary(): string
    {
        return count($this->stats) . ' stats updated';
    }
}
