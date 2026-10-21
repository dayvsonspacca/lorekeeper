<?php

declare(strict_types=1);

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Lorekeeper\Apps\Desktop\Application;
use Lorekeeper\Apps\Desktop\Native\BootstrapCache;

BootstrapCache::redirect();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(health: '/up')
    ->withCommands([__DIR__ . '/../app/Console/Commands'])
    ->withMiddleware(static function (Middleware $middleware): void {})
    ->withExceptions(static function (Exceptions $exceptions): void {})
    ->create();
