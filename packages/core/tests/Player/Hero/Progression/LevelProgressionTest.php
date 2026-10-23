<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Progression;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\Hero\Progression\LevelProgression;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LevelProgressionTest extends TestCase
{
    #[Test]
    public function it_earns_experience_up_to_what_the_level_needs(): void
    {
        $level = new LevelProgression(new Gauge(new Experience(4_514_754), new Experience(5_280_000)));

        $level->earn(new Experience(1000));
        static::assertSame(4_515_754, $level->experience->current->value);

        $level->earn(new Experience(9_999_999));
        static::assertSame(5_280_000, $level->experience->current->value);
    }

    #[Test]
    public function the_next_level_starts_from_no_experience(): void
    {
        $level = new LevelProgression(new Gauge(new Experience(5_280_000), new Experience(5_280_000)));

        $level->next(new Experience(5_600_000));

        static::assertSame([0, 5_600_000], [$level->experience->current->value, $level->experience->maximum->value]);
    }

    #[Test]
    public function it_takes_whatever_the_server_recounts(): void
    {
        $level = new LevelProgression(new Gauge(new Experience(0), new Experience(100)));

        $level->recount(new Gauge(new Experience(4_514_754), new Experience(5_280_000)));

        static::assertSame([4_514_754, 5_280_000], [
            $level->experience->current->value,
            $level->experience->maximum->value,
        ]);
    }
}
