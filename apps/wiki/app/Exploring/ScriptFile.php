<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Exploring;

use Lorekeeper\Client\Script\Script;
use RuntimeException;

final readonly class ScriptFile
{
    /**
     * @throws RuntimeException
     *
     * @return Script<mixed>
     */
    public static function load(string $path): Script
    {
        if (!is_file($path)) {
            throw new RuntimeException("There is no script at {$path}.");
        }

        /** @var mixed $script */
        $script = (static fn(string $file): mixed => require $file)($path);
        if (!$script instanceof Script) {
            throw new RuntimeException("{$path} does not return a script.");
        }

        return $script;
    }
}
