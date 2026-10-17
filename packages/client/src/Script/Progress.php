<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

final readonly class Progress implements Instruction
{
    /**
     * @param non-negative-int $done
     * @param positive-int $total
     */
    public function __construct(
        public int $done,
        public int $total,
    ) {}

    public function ratio(): float
    {
        return min(1.0, $this->done / $this->total);
    }
}
