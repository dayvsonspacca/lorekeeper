<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

enum Pad
{
    case Left;
    case Right;
    case Top;
    case Bottom;
    case Up;
    case Down;
    case Center;
    case Spawn;
}
