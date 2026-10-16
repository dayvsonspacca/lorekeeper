<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot;

use Lorekeeper\Core\Player\PlayerName;
use SensitiveParameter;

final readonly class Credentials
{
    /** @param null|string $server The server to join; the first open one when null. */
    public function __construct(
        public PlayerName $player,
        #[SensitiveParameter]
        public string $password,
        public ?string $server = null,
    ) {}
}
