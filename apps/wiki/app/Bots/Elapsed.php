<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Illuminate\Support\Carbon;

final readonly class Elapsed
{
    public static function between(?Carbon $start, ?Carbon $end): ?string
    {
        if ($start === null) {
            return null;
        }

        $seconds = (int) $start->diffInSeconds($end ?? now());

        return match (true) {
            $seconds < 60 => "{$seconds}s",
            $seconds < 3600 => sprintf('%dm %02ds', intdiv($seconds, num2: 60), $seconds % 60),
            default => sprintf('%dh %02dm', intdiv($seconds, num2: 3600), intdiv($seconds % 3600, num2: 60)),
        };
    }
}
