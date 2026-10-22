<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tauri;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorekeeper\Apps\Desktop\Native\Heartbeat;
use Lorekeeper\Apps\Desktop\Native\Tray;

final readonly class Poll
{
    public const string SECRET_HEADER = 'X-Lorekeeper-Secret';

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(Shell::trusts($request->header(self::SECRET_HEADER)), code: 403);

        return response()->json([
            'bots' => Tray::bots(),
            'playing' => Tray::playing(),
            'notices' => TauriNotifier::drain(),
            'reload' => Heartbeat::revive(),
        ]);
    }
}
