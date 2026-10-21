<?php

declare(strict_types=1);

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Lorekeeper\Apps\Desktop\Application;
use Lorekeeper\Apps\Desktop\Native\BootstrapCache;
use Lorekeeper\Apps\Desktop\Tauri\Poll;
use Lorekeeper\Apps\Desktop\Tauri\RequireShell;

BootstrapCache::redirect();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(health: '/up', then: static fn() => Route::get('/shell', Poll::class))
    ->withCommands([__DIR__ . '/../app/Console/Commands'])
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->prepend(RequireShell::class);
        $middleware->encryptCookies(except: [RequireShell::COOKIE]);
    })
    ->withExceptions(static function (Exceptions $exceptions): void {})
    ->create();
