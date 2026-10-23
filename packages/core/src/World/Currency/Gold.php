<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Currency;

use Override;

final readonly class Gold extends Currency
{
    #[Override]
    public function symbol(): string
    {
        return 'G';
    }
}
