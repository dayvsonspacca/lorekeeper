<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

final readonly class Palette
{
    private const array HUES = ['warrior' => 22, 'mage' => 222, 'healer' => 150, 'rogue' => 285];

    public static function hue(string $name): int
    {
        $name = strtolower($name);

        return self::HUES[$name] ?? (hexdec(substr(md5($name), offset: 0, length: 6)) % 360);
    }

    public static function race(string $race): string
    {
        return match ($race) {
            'undead' => '#a3e635',
            'human' => '#fbbf24',
            'elemental' => '#22d3ee',
            'dragonkin' => '#f87171',
            default => '#94a3b8',
        };
    }

    public static function feed(string $kind): string
    {
        return match (FeedKind::tryFrom($kind)) {
            FeedKind::Defeated => '#fca5a5',
            FeedKind::Died => '#f87171',
            FeedKind::Revived => '#86efac',
            FeedKind::LeveledUp => '#fbbf24',
            FeedKind::RankedUp => '#c4b5fd',
            FeedKind::Travelled => '#7dd3fc',
            null => '#94a3b8',
        };
    }
}
