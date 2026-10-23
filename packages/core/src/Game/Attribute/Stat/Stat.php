<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat;

interface Stat
{
    public function describe(): StatDescription;
}
