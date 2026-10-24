<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

interface Versions
{
    public function current(): ClientVersion;

    public function learn(ClientVersion $version): void;
}
