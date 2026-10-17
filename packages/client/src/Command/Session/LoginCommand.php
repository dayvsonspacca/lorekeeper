<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Session;

use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Player\PlayerName;
use Override;
use SensitiveParameter;

final readonly class LoginCommand implements Command
{
    public function __construct(
        public PlayerName $playerName,
        #[SensitiveParameter]
        public string $token,
        public ClientVersion $version,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return (
            "<msg t='sys'>"
            . "<body action='login' r='0'>"
            . "<login z='zone_master'>"
            . "<nick><![CDATA[SPIDER#0001~{$this->playerName}~{$this->version}]]></nick>"
            . "<pword><![CDATA[{$this->token}]]></pword>"
            . '</login></body></msg>'
        );
    }
}
