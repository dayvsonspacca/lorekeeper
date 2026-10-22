<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notice;
use Lorekeeper\Apps\Desktop\Models\Account;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NoticeTest extends TestCase
{
    #[Test]
    public function a_bot_tells_only_when_it_left_the_game_on_its_own(): void
    {
        $leaving = static function (BotStatus $status, ?string $result = null): ?array {
            $account = new Account();
            $account->forceFill(['name' => 'Hero', 'status' => $status, 'result' => $result]);
            $notice = Notice::leaving($account);

            return $notice === null ? null : [$notice->title, $notice->message];
        };

        static::assertSame(
            ['Hero', 'Disconnected from the game'],
            $leaving(BotStatus::Disconnected, result: 'connection lost'),
        );
        static::assertSame(['Hero', 'Failed: wrong password'], $leaving(BotStatus::Failed, result: 'wrong password'));
        static::assertNull($leaving(BotStatus::Offline));
    }
}
