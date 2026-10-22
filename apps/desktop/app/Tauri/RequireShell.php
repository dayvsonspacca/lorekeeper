<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tauri;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireShell
{
    public const string COOKIE = 'lorekeeper_shell';
    public const string QUERY = 'lorekeeper_secret';

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Shell::running() || $request->is('up')) {
            return $next($request);
        }

        if ($request->query->has(self::QUERY)) {
            $secret = self::text($request->query(self::QUERY));
            abort_unless(Shell::trusts($secret), code: 403);

            return redirect($request->fullUrlWithoutQuery([self::QUERY]))
                ->withCookie(
                    Cookie::create(self::COOKIE, $secret)->withHttpOnly(true)->withSameSite(Cookie::SAMESITE_STRICT),
                );
        }

        abort_unless(
            Shell::trusts(self::text($request->cookie(self::COOKIE)))
            || Shell::trusts($request->header(Poll::SECRET_HEADER)),
            code: 403,
        );

        return $next($request);
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
