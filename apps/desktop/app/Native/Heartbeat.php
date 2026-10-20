<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Illuminate\Support\Facades\Cache;

final readonly class Heartbeat
{
    public const int PATIENCE_SECONDS = 90;

    private const string KEY = 'lorekeeper.window.heartbeat';

    public static function beat(): void
    {
        Cache::forever(self::KEY, now()->getTimestamp());
    }

    public static function silent(): bool
    {
        $last = Cache::get(self::KEY);

        return is_int($last) && (now()->getTimestamp() - $last) > self::PATIENCE_SECONDS;
    }
}
