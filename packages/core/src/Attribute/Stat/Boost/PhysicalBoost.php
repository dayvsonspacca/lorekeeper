<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Boost;

use Lorekeeper\Core\Attribute\Stat\StatDescription;
use Override;

final readonly class PhysicalBoost extends Boost
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('Increases outgoing physical and hybrid damage.');
    }
}
