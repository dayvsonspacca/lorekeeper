<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;

final readonly class Identity
{
    public function __construct(
        public PlayerSpawnId $spawnId,
        public PlayerName $name,
    ) {}

    public function is(PlayerName $name): bool
    {
        return strcasecmp($name->value, $this->name->value) === 0;
    }

    public function owns(PlayerSpawnId $spawnId): bool
    {
        return $spawnId->equals($this->spawnId);
    }
}
