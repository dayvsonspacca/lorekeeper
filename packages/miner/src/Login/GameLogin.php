<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Login;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Protocol\ClientVersion;
use SensitiveParameter;

final readonly class GameLogin
{
    public function __construct(
        public PlayerName $player,
        #[SensitiveParameter]
        public string $token,
        public ClientVersion $version,
    ) {}
}
