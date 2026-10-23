<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Primary;

use Lorekeeper\Core\Game\Attribute\Stat\StatDescription;
use Override;

final readonly class Endurance extends PrimaryAttribute
{
    #[Override]
    public function describe(): StatDescription
    {
        return new StatDescription(
            'Endurance directly contributes to your total Hit Points. While very useful for all classes, some abilities work best with very high or very low total HP.',
        );
    }
}
