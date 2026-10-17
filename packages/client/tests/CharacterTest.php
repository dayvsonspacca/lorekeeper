<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use LogicException;
use Lorekeeper\Client\Character;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerMana;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Map\MapPlayer;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Lorekeeper\Server\Event\Map\MonstersPlacedEvent;
use Lorekeeper\Server\Event\Map\PlayersPresentEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class CharacterTest extends TestCase
{
    #[Test]
    public function it_learns_about_itself_from_what_the_server_says(): void
    {
        $me = new Character(new PlayerName('lorekeeper03'));
        $fireball = new Skill(SkillSlot::First, SkillTarget::Enemy, new Mana(20), Duration::seconds(6));
        $shard = new Skill(SkillSlot::Second, SkillTarget::Enemy, new Mana(15), Duration::seconds(6));

        $me->hear(new PlayersPresentEvent([
            new MapPlayer(new PlayerName('Someone'), new PlayerLevel(80)),
            new MapPlayer(new PlayerName('LoreKeeper03'), new PlayerLevel(1)),
        ]));
        $me->hear(new SkillsLoadedEvent([$fireball, $shard], [SkillSlot::First]));
        $me->hear(new PlayersManaChangedEvent([new PlayerMana(new PlayerName('lorekeeper03'), new Mana(87))]));
        $me->hear(new MonstersPlacedEvent([
            new MonsterPlacement(new MonsterSpawnId(4), new MonsterId(2033), new Cell('r3'), null),
        ]));

        static::assertSame(1, $me->level()->value);
        static::assertSame([$fireball], $me->skills());
        static::assertSame(87, $me->mana()?->value);
        static::assertCount(1, $me->map()->monstersIn(new Cell('r3')));
        static::assertSame([], $me->map()->monstersIn(new Cell('r4')));

        $me->hear(new LeveledUpEvent(new PlayerLevel(2), new Experience(100)));
        $me->hear(new PlayerDiedEvent());
        static::assertSame(2, $me->level()->value);
        static::assertFalse($me->alive());

        $me->hear(new ResurrectedEvent());
        static::assertTrue($me->alive());
    }

    #[Test]
    public function it_does_not_know_where_it_is_before_entering_the_game(): void
    {
        $this->expectException(LogicException::class);

        new Character(new PlayerName('Hero'))->position();
    }
}
