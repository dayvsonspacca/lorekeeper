<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\RoutineSteps;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoutineStepsTest extends TestCase
{
    #[Test]
    public function it_levels_up_in_steps_and_enhances_after_each_one(): void
    {
        static::assertSame(
            [
                ['script' => 'level-up', 'parameters' => ['level' => 90]],
                ['script' => 'enhance', 'parameters' => ['type' => 'Lucky']],
                ['script' => 'level-up', 'parameters' => ['level' => 95]],
                ['script' => 'enhance', 'parameters' => ['type' => 'Lucky']],
                ['script' => 'level-up', 'parameters' => ['level' => 100]],
                ['script' => 'enhance', 'parameters' => ['type' => 'Lucky']],
            ],
            RoutineSteps::levelUp(90, 100, 5, 'Lucky'),
        );
    }

    #[Test]
    public function the_last_step_stops_at_the_target_even_off_the_beat(): void
    {
        static::assertSame(
            [
                ['script' => 'level-up', 'parameters' => ['level' => 30]],
                ['script' => 'level-up', 'parameters' => ['level' => 37]],
                ['script' => 'level-up', 'parameters' => ['level' => 40]],
            ],
            RoutineSteps::levelUp(30, 40, 7, null),
        );
    }

    #[Test]
    public function the_full_climb_to_level_one_hundred_takes_twenty_steps_of_five(): void
    {
        static::assertCount(40, RoutineSteps::levelUp(5, 100, 5, 'Lucky'));
    }
}
