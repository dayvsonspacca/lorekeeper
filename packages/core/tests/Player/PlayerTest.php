<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class PlayerTest extends TestCase
{
    #[Test]
    public function a_player_knows_its_level_with_the_experience_toward_the_next_and_its_wallet(): void
    {
        $player = self::made2903();

        static::assertSame(81, $player->progress->level->value);
        static::assertSame([4_514_754, 5_280_000], [
            $player->progress->experience->current->value,
            $player->progress->experience->maximum->value,
        ]);
        static::assertSame([6_800_132, 2899], [$player->wallet->gold->value, $player->wallet->coins->value]);
    }

    #[Test]
    public function a_player_is_a_member_once_tagged_so(): void
    {
        $player = self::made2903();

        static::assertFalse($player->tags->has(PlayerTag::Member));

        $player->tags->add(PlayerTag::Member);

        static::assertTrue($player->tags->has(PlayerTag::Member));
    }

    private static function made2903(): Player
    {
        return new Player(
            new PlayerName('made2903'),
            new PlayerLevelProgress(
                new PlayerLevel(81),
                new Gauge(new Experience(4_514_754), new Experience(5_280_000)),
            ),
            new Vitals(new Gauge(new Health(2585), new Health(2585)), new Gauge(new Mana(100), new Mana(100))),
            new PlayerClass(
                new CharacterClass(new ClassName('Blaze Binder'), ClassCategory::Caster, Option\none(), [], []),
                new ClassPoints(302_500),
                [],
            ),
            new PlayerInventory(new PlayerItems([]), new Quantity(131)),
            new Wallet(new Gold(6_800_132), new AdventureCoins(2899)),
            new PlayerQuestMarks(),
        );
    }
}
