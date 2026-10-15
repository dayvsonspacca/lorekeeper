<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Psl\Network\Address;

final readonly class Server
{
    /** @param non-empty-string $name */
    public function __construct(
        public string $name,
        public Address $address,
        public bool $online,
        public bool $memberOnly,
    ) {}
}
