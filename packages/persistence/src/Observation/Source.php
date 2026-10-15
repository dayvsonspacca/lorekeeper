<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Observation;

enum Source: string
{
    case Socket = 'socket';
    case TravelMap = 'travel-map';
    case Swf = 'swf';
    case Manual = 'manual';
}
