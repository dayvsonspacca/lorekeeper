<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Cli;

final readonly class Password
{
    private const string VARIABLE = 'AQW_PASSWORD';

    public static function read(): string
    {
        $password = getenv(self::VARIABLE);
        if ($password !== false && $password !== '') {
            return $password;
        }

        fwrite(STDOUT, data: 'Password: ');
        shell_exec('stty -echo');
        $password = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        fwrite(STDOUT, data: "\n");

        return $password;
    }
}
