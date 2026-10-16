<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

final readonly class Done
{
    public function __construct(
        public string $summary,
    ) {}
}
