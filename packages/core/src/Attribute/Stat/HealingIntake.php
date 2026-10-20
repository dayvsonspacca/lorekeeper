<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat;

use Lorekeeper\Core\Support\Ratio;
use Override;

final readonly class HealingIntake extends Ratio implements Stat
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription('How much of the healing received from every source takes effect.');
    }
}
