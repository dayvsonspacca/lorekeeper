<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Cli;

use Lorekeeper\Miner\Server;
use Psl\Option;

final readonly class ServerChoice
{
    /**
     * @param list<Server> $servers
     *
     * @return Option\Option<Server>
     */
    public static function pick(array $servers, ?string $name = null): Option\Option
    {
        foreach ($servers as $server) {
            if ($server->online && !$server->memberOnly && ($name ?? $server->name) === $server->name) {
                return Option\some($server);
            }
        }

        return Option\none();
    }
}
