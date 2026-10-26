<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map\Helper;

use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;

final readonly class MapPads
{
    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     */
    public static function read(string $pad, string $event): MapPad
    {
        return match (strtolower($pad)) {
            'left' => MapPad::Left,
            'right' => MapPad::Right,
            'top' => MapPad::Top,
            'bottom' => MapPad::Bottom,
            'up' => MapPad::Up,
            'down' => MapPad::Down,
            'center' => MapPad::Center,
            'spawn' => MapPad::Spawn,
            default => throw new MalformedEventException($event, "unknown pad '{$pad}'"),
        };
    }
}
