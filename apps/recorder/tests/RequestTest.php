<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder\Tests;

use Lorekeeper\Apps\Recorder\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    #[Test]
    public function it_takes_the_script_file_and_how_long_to_play_and_the_account_from_the_environment(): void
    {
        $request = Request::parse(['scripts/LevelUp.php', '--for=300'], [
            ...self::environment(),
            'AQW_SERVER' => 'Twilly',
        ]);

        static::assertNotNull($request);
        static::assertSame(['scripts/LevelUp.php', 'Hero', 'Twilly', 300], [
            $request->script,
            $request->player,
            $request->server,
            (int) $request->length->getTotalSeconds(),
        ]);
    }

    #[Test]
    public function it_plays_for_a_minute_on_any_server_unless_told_otherwise(): void
    {
        $request = Request::parse(['scripts/Watch.php'], self::environment());

        static::assertNotNull($request);
        static::assertNull($request->server);
        static::assertSame(60, (int) $request->length->getTotalSeconds());
    }

    #[Test]
    public function it_needs_a_script_and_the_account_in_the_environment(): void
    {
        static::assertNull(Request::parse([], self::environment()));
        static::assertNull(Request::parse(['scripts/Watch.php'], ['AQW_PLAYER' => 'Hero']));
    }

    /** @return array<string, string> */
    private static function environment(): array
    {
        return ['AQW_PLAYER' => 'Hero', 'AQW_PASSWORD' => bin2hex(random_bytes(8))];
    }
}
