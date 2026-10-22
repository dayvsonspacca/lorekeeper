<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tauri;

use Illuminate\Support\Facades\Cache;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notice;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Override;

final readonly class TauriNotifier implements Notifier
{
    private const string KEY = 'lorekeeper.tauri.notices';

    #[Override]
    public function show(Notice $notice): void
    {
        Cache::lock(self::KEY . '.lock', seconds: 5)->block(5, static function () use ($notice): void {
            Cache::forever(self::KEY, [...self::pending(), ['title' => $notice->title, 'message' => $notice->message]]);
        });
    }

    /** @return list<array{title: string, message: string}> */
    public static function drain(): array
    {
        return Cache::lock(self::KEY . '.lock', seconds: 5)->block(5, static function (): array {
            $notices = self::pending();
            Cache::forget(self::KEY);

            return $notices;
        });
    }

    /** @return list<array{title: string, message: string}> */
    private static function pending(): array
    {
        $notices = Cache::get(self::KEY);

        return is_array($notices) ? array_values($notices) : [];
    }
}
