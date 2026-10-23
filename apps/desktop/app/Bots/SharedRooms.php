<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapRoom;
use SensitiveParameter;

final readonly class SharedRooms
{
    public static function of(MapName $map): MapRoom
    {
        return self::for($map, (string) config('app.key'));
    }

    public static function for(MapName $map, #[SensitiveParameter] string $secret): MapRoom
    {
        $span = PrivateRooms::HIGHEST - PrivateRooms::LOWEST + 1;
        $hash = hexdec(substr(hash_hmac('sha256', strtolower($map->value), $secret), offset: 0, length: 12));

        return new MapRoom(PrivateRooms::LOWEST + ((int) $hash % $span));
    }
}
