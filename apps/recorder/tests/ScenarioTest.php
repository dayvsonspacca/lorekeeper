<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder\Tests;

use Lorekeeper\Apps\Recorder\Scenario;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class ScenarioTest extends TestCase
{
    #[Test]
    public function each_scenario_plays_its_script(): void
    {
        static::assertSame('Watch for 90s', Scenario::Watch->script([], Duration::seconds(90))->title());
        static::assertSame(
            'Level up to 12',
            Scenario::LevelUp->script(['level' => '12'], Duration::seconds(90))->title(),
        );
        static::assertSame('Level up to 100', Scenario::LevelUp->script([], Duration::seconds(90))->title());
    }
}
