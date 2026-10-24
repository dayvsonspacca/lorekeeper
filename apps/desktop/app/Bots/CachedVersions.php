<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Illuminate\Support\Facades\Cache;
use Lorekeeper\Client\Session\ClientVersion;
use Lorekeeper\Client\Session\Login;
use Lorekeeper\Client\Session\Versions;
use Override;

final readonly class CachedVersions implements Versions
{
    private const string KEY = 'lorekeeper.client.version';

    #[Override]
    public function current(): ClientVersion
    {
        $kept = Cache::get(self::KEY);

        return new ClientVersion(is_string($kept) && $kept !== '' ? $kept : Login::VERSION);
    }

    #[Override]
    public function learn(ClientVersion $version): void
    {
        Cache::forever(self::KEY, $version->value);
    }
}
