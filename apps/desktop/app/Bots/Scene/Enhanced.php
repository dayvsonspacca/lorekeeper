<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementEffect;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;

/**
 * @psalm-type Enhancement = array{type: string, rarity: string, level: int, effect: ?array{name: string, description: string}}
 */
final readonly class Enhanced
{
    /** @return ?Enhancement */
    public static function of(Item $item): ?array
    {
        $applied = match (true) {
            $item instanceof EnhancementItem => $item->enhancement,
            $item instanceof Weapon,
            $item instanceof ClassItem,
            $item instanceof Helm,
            $item instanceof Cape,
                => $item->enhancement->unwrapOr(null),
            default => null,
        };
        if ($applied === null) {
            return null;
        }

        return [
            'type' => self::words($applied->type->name),
            'rarity' => self::rarity($applied->rarity),
            'level' => $applied->level->value,
            'effect' => $applied
                ->effect
                ->map(static fn(EnhancementEffect $effect): array => [
                    'name' => $effect->name->value,
                    'description' => $effect->description->value,
                ])
                ->unwrapOr(null),
        ];
    }

    private static function rarity(EnhancementRarity $rarity): string
    {
        return match ($rarity) {
            EnhancementRarity::PlusZero => '+0',
            EnhancementRarity::PlusOne => '+1',
            EnhancementRarity::PlusTwo => '+2',
            EnhancementRarity::PlusThree => '+3',
            EnhancementRarity::PlusFour => '+4',
            EnhancementRarity::PlusFive => '+5',
            EnhancementRarity::PlusSix => '+6',
            EnhancementRarity::PlusOneHundred => '+100',
            EnhancementRarity::PlusTwoHundred => '+200',
        };
    }

    private static function words(string $name): string
    {
        return trim((string) preg_replace('/(?<!^)[A-Z]/', replacement: ' $0', subject: $name));
    }
}
