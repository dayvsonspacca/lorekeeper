<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\ClassPoints;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\Hero\HeroClass;
use Lorekeeper\Core\Player\Hero\HeroInventory;
use Lorekeeper\Core\Player\Hero\HeroLevelProgress;
use Lorekeeper\Core\Player\Hero\HeroQuestMarks;
use Lorekeeper\Core\Player\Hero\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class HeroTest extends TestCase
{
    #[Test]
    public function a_hero_knows_its_level_with_the_experience_toward_the_next_and_its_wallet(): void
    {
        $hero = self::hero();

        static::assertSame(81, $hero->level->value);
        static::assertSame($hero->level, $hero->progress->level);
        static::assertSame([4_514_754, 5_280_000], [
            $hero->progress->experience->current->value,
            $hero->progress->experience->maximum->value,
        ]);
        static::assertSame([6_800_132, 2899], [$hero->wallet->gold->value, $hero->wallet->coins->value]);
    }

    #[Test]
    public function a_hero_is_a_member_once_tagged_so(): void
    {
        $hero = self::hero();

        static::assertFalse($hero->tags->has(PlayerTag::Member));

        $hero->tags->add(PlayerTag::Member);

        static::assertTrue($hero->tags->has(PlayerTag::Member));
    }

    private static function hero(): Hero
    {
        return new Hero(
            new PlayerName('Hero'),
            new HeroLevelProgress(new PlayerLevel(81), new Gauge(new Experience(4_514_754), new Experience(5_280_000))),
            new Vitals(new Gauge(new Health(2585), new Health(2585)), new Gauge(new Mana(100), new Mana(100))),
            new HeroClass(
                new CharacterClass(new ClassName('Blaze Binder'), ClassCategory::Caster, Option\none(), [], []),
                new ClassPoints(302_500),
                [],
            ),
            new HeroInventory(new Quantity(131)),
            new HeroWallet(new Gold(6_800_132), new AdventureCoins(2899)),
            new HeroQuestMarks(),
        );
    }
}
