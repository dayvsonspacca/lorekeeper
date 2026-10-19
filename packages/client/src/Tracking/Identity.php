<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\SocketId;

final readonly class Identity
{
    public function __construct(
        public SocketId $socketId,
        public PlayerName $name,
    ) {}

    public function is(PlayerName $name): bool
    {
        return strcasecmp($name->value, $this->name->value) === 0;
    }

    public function owns(SocketId $socketId): bool
    {
        return $socketId->value === $this->socketId->value;
    }
}
