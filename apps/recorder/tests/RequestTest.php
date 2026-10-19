<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder\Tests;

use Lorekeeper\Apps\Recorder\Request;
use Lorekeeper\Apps\Recorder\Scenario;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    #[Test]
    public function it_reads_the_scenario_and_its_options_and_takes_the_account_from_the_environment(): void
    {
        $request = Request::parse(['level-up', '--for=300', '--level=12'], [
            ...self::environment(),
            'AQW_SERVER' => 'Twilly',
        ]);

        static::assertNotNull($request);
        static::assertSame(Scenario::LevelUp, $request->scenario);
        static::assertSame(['lorekeeper03', 'Twilly', 300], [
            $request->player,
            $request->server,
            (int) $request->length->getTotalSeconds(),
        ]);
        static::assertSame(['for' => '300', 'level' => '12'], $request->options);
    }

    #[Test]
    public function it_watches_for_a_minute_on_any_server_unless_told_otherwise(): void
    {
        $request = Request::parse(['watch'], self::environment());

        static::assertNotNull($request);
        static::assertNull($request->server);
        static::assertSame(60, (int) $request->length->getTotalSeconds());
    }

    #[Test]
    public function it_needs_a_known_scenario_and_the_account_in_the_environment(): void
    {
        static::assertNull(Request::parse([], self::environment()));
        static::assertNull(Request::parse(['dance'], self::environment()));
        static::assertNull(Request::parse(['watch'], ['AQW_PLAYER' => 'lorekeeper03']));
    }

    /** @return array<string, string> */
    private static function environment(): array
    {
        return ['AQW_PLAYER' => 'lorekeeper03', 'AQW_PASSWORD' => bin2hex(random_bytes(8))];
    }
}
