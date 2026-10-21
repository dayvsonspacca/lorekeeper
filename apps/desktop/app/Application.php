<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop;

use Illuminate\Foundation\Application as LaravelApplication;

final class Application extends LaravelApplication
{
    public function __construct(?string $basePath = null)
    {
        $this->absoluteCachePathPrefixes = [
            ...$this->absoluteCachePathPrefixes,
            ...array_map(static fn(string $drive): string => "{$drive}:", [
                ...range('A', end: 'Z'),
                ...range('a', end: 'z'),
            ]),
        ];

        parent::__construct($basePath);
    }
}
