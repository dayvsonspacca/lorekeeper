<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Script\Game\IdleScript;
use Lorekeeper\Client\Script\Pause;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class IdleScriptTest extends TestCase
{
    #[Test]
    public function it_keeps_pausing_until_it_is_stopped(): void
    {
        $steps = new IdleScript()->run();

        for ($minute = 0; $minute < 3; $minute++) {
            static::assertEquals(new Pause(Duration::minutes(1)), $steps->current());
            $steps->send(null);
        }

        static::assertTrue($steps->valid());
    }
}
