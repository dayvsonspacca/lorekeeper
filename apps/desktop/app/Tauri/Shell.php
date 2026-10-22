<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tauri;

use SensitiveParameter;

final readonly class Shell
{
    public static function running(): bool
    {
        return config('desktop.shell') === 'tauri';
    }

    public static function alive(): bool
    {
        $pid = config('desktop.shell_pid');
        if (!self::running() || !is_numeric($pid)) {
            return true;
        }

        return posix_kill((int) $pid, signal: 0);
    }

    public static function trusts(#[SensitiveParameter] ?string $secret): bool
    {
        $expected = config('desktop.shell_secret');

        return self::running() && is_string($expected) && $expected !== '' && hash_equals($expected, (string) $secret);
    }
}
