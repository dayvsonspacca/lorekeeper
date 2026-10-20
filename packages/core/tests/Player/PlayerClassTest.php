<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Progression\ClassPoints;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class PlayerClassTest extends TestCase
{
    #[Test]
    #[TestWith([0, 1])]
    #[TestWith([3_599, 2])]
    #[TestWith([302_500, 10])]
    public function it_ranks_the_player_by_the_points_they_have_in_the_class(int $points, int $rank): void
    {
        $mage = new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), [], []);

        static::assertSame($rank, new PlayerClass($mage, new ClassPoints($points), [])->rank()->value);
    }
}
