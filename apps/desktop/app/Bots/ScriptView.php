<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

enum ScriptView: string
{
    case Plate = 'plate';
    case Panel = 'panel';
    case Report = 'report';
}
