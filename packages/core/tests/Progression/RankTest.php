<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Progression;

use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Faction\Reputation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class RankTest extends TestCase
{
    #[Test]
    #[TestWith([1])]
    #[TestWith([10])]
    public function it_accepts_ranks_within_range(int $rank): void
    {
        static::assertSame($rank, new Rank($rank)->value);
    }

    #[Test]
    #[TestWith([0])]
    #[TestWith([11])]
    public function it_rejects_ranks_out_of_range(int $rank): void
    {
        $this->expectException(AssertException::class);

        new Rank($rank);
    }

    #[Test]
    #[TestWith([0, 1])]
    #[TestWith([899, 1])]
    #[TestWith([900, 2])]
    #[TestWith([3600, 3])]
    #[TestWith([177_790, 8])]
    #[TestWith([302_499, 9])]
    #[TestWith([302_500, 10])]
    #[TestWith([999_999, 10])]
    public function it_resolves_rank_from_class_points(int $points, int $rank): void
    {
        static::assertSame($rank, Rank::fromPoints(new ClassPoints($points))->value);
    }

    #[Test]
    public function it_resolves_rank_from_reputation(): void
    {
        static::assertSame(10, Rank::fromPoints(new Reputation(302_500))->value);
    }

    #[Test]
    #[TestWith([1, 0])]
    #[TestWith([2, 900])]
    #[TestWith([10, 302_500])]
    public function it_knows_the_minimum_points_of_each_rank(int $rank, int $points): void
    {
        static::assertSame($points, new Rank($rank)->minimumPoints());
    }
}
