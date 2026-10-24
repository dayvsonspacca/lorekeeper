<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

use Override;

final class InMemoryVersions implements Versions
{
    private ClientVersion $current;

    public function __construct(?ClientVersion $current = null)
    {
        $this->current = $current ?? new ClientVersion(Login::VERSION);
    }

    #[Override]
    public function current(): ClientVersion
    {
        return $this->current;
    }

    #[Override]
    public function learn(ClientVersion $version): void
    {
        $this->current = $version;
    }
}
