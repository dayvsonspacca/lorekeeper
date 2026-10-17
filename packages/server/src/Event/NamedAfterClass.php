<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

trait NamedAfterClass
{
    public function name(): string
    {
        $class = static::class;

        return (string) preg_replace(
            '/Event$/',
            replacement: '',
            subject: substr($class, (int) strrpos($class, needle: '\\') + 1),
        );
    }
}
