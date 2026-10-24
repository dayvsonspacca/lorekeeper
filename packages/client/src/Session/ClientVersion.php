<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

use Lorekeeper\Core\Support\NonEmptyString;

final readonly class ClientVersion extends NonEmptyString
{
    public static function demandedIn(string $refusal): ?self
    {
        $found = [];
        if (preg_match('/game version (\d+(?:\.\d+)+)/i', $refusal, $found) !== 1) {
            return null;
        }

        return new self($found[1]);
    }
}
