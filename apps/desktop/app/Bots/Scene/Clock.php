<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

final readonly class Clock
{
    public static function ago(int $at, int $now): string
    {
        $seconds = max(0, $now - $at);

        return match (true) {
            $seconds < 5 => 'now',
            $seconds < 60 => "{$seconds}s ago",
            $seconds < 3600 => intdiv($seconds, num2: 60) . 'm ago',
            $seconds < 86_400 => intdiv($seconds, num2: 3600) . 'h ago',
            default => intdiv($seconds, num2: 86_400) . 'd ago',
        };
    }
}
