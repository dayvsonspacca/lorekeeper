<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;

interface FactEvent extends Event
{
    /** @return list<Fact<Subject, object>> */
    public function facts(): array;
}
