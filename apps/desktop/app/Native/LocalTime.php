<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Illuminate\Support\Carbon;
use IntlTimeZone;

final readonly class LocalTime
{
    public static function detect(): string
    {
        $zone = class_exists(IntlTimeZone::class) ? IntlTimeZone::createDefault()->getID() : false;

        return is_string($zone) && in_array($zone, timezone_identifiers_list(), strict: true) ? $zone : 'UTC';
    }

    public static function zone(): string
    {
        $zone = config('desktop.timezone');

        return is_string($zone) ? $zone : 'UTC';
    }

    public static function clock(Carbon $at): string
    {
        return $at->copy()->setTimezone(self::zone())->format('H:i:s');
    }

    public static function startOfToday(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->setTimezone(self::zone())->startOfDay()->utc();
    }
}
