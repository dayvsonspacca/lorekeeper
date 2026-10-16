<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Reward;

use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExperienceRewardTest extends TestCase
{
    #[Test]
    public function it_holds_the_experience(): void
    {
        static::assertSame(1500, new ExperienceReward(new Experience(1500))->experience->value);
    }
}
