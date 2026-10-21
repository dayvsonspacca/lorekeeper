<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use InvalidArgumentException;

final class InvalidFlowFile extends InvalidArgumentException
{
    /** @param non-empty-list<string> $problems */
    public function __construct(
        public readonly array $problems,
    ) {
        parent::__construct(implode('; ', $problems));
    }
}
