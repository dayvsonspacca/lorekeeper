<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scene;

use Lorekeeper\Apps\Desktop\Bots\Scene\Pulse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PulseTest extends TestCase
{
    private const int NOW = (60 * 1_000_000) + 30;

    #[Test]
    public function it_adds_up_the_experience_and_kills_of_each_minute(): void
    {
        $pulse = new Pulse()
            ->rewarded(self::NOW - 125, experience: 30)
            ->rewarded(self::NOW - 61, experience: 20)
            ->rewarded(self::NOW - 60, experience: 25)
            ->rewarded(self::NOW, experience: 40);

        static::assertCount(Pulse::MINUTES, $pulse->experience(self::NOW));
        static::assertSame([0, 30, 45, 40], array_slice($pulse->experience(self::NOW), offset: -4));
        static::assertSame([0, 1, 2, 1], array_slice($pulse->kills(self::NOW), offset: -4));
    }

    #[Test]
    public function it_forgets_the_minutes_older_than_its_window(): void
    {
        $pulse = new Pulse()
            ->rewarded(self::NOW - (Pulse::MINUTES * 60), experience: 99)
            ->rewarded(self::NOW, experience: 1);

        static::assertSame([intdiv(self::NOW, num2: 60) => [1, 1]], $pulse->minutes);
    }

    #[Test]
    public function it_tells_the_experience_per_hour_from_the_finished_minutes_since_it_began(): void
    {
        $pulse = new Pulse()
            ->rewarded(self::NOW - 120, experience: 100)
            ->rewarded(self::NOW - 60, experience: 200)
            ->rewarded(self::NOW, experience: 5000);

        static::assertSame(9000, $pulse->experiencePerHour(self::NOW));
        static::assertSame(0, new Pulse()->experiencePerHour(self::NOW));
    }
}
