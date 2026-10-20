<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\RoomId;
use SensitiveParameter;

final readonly class SharedRooms
{
    public static function of(MapName $map): RoomId
    {
        return self::for($map, (string) config('app.key'));
    }

    public static function for(MapName $map, #[SensitiveParameter] string $secret): RoomId
    {
        $span = PrivateRooms::HIGHEST - PrivateRooms::LOWEST + 1;
        $hash = hexdec(substr(hash_hmac('sha256', strtolower($map->value), $secret), offset: 0, length: 12));

        return new RoomId(PrivateRooms::LOWEST + ((int) $hash % $span));
    }
}
