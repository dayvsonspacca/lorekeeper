<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

final readonly class Wire
{
    /**
     * @param non-empty-string $from
     * @param non-empty-string $exit
     * @param non-empty-string $to
     */
    public function __construct(
        public string $from,
        public string $exit,
        public string $to,
    ) {}
}
