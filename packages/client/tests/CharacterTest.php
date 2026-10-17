<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use LogicException;
use Lorekeeper\Client\Character;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerMana;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Lorekeeper\Server\Event\Map\MonstersPlacedEvent;
use Lorekeeper\Server\Event\Map\PlayersSeenEvent;
use Lorekeeper\Server\Event\Map\SeenPlayer;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;

final class CharacterTest extends TestCase
{
    #[Test]
    public function it_learns_about_itself_from_what_the_server_says(): void
    {
        $me = new Character(new PlayerName('lorekeeper03'));
        $fireball = new Skill(SkillSlot::First, SkillTarget::Enemy, new Mana(20), Duration::seconds(6));
        $shard = new Skill(SkillSlot::Second, SkillTarget::Enemy, new Mana(15), Duration::seconds(6));

        $me->hear(new PlayersSeenEvent([
            self::seen('Someone', 80),
            self::seen('LoreKeeper03', 1),
        ]));
        $me->hear(new SkillsLoadedEvent([$fireball, $shard], [SkillSlot::First]));
        $me->hear(new PlayersManaChangedEvent([new PlayerMana(new PlayerName('lorekeeper03'), new Mana(87))]));
        $me->hear(new MonstersPlacedEvent([
            new MonsterPlacement(new MonsterSpawnId(4), new MonsterId(2033), new Cell('r3')),
        ]));

        static::assertSame(1, $me->level()->value);
        static::assertSame([$fireball], $me->skills());
        static::assertSame(87, $me->mana()?->value);
        static::assertSame([], $me->map()->cells());

        $me->hear(new LeveledUpEvent(new PlayerLevel(2), new Experience(100)));
        $me->hear(new PlayerDiedEvent());
        static::assertSame(2, $me->level()->value);
        static::assertFalse($me->alive());

        $me->hear(new ResurrectedEvent());
        static::assertTrue($me->alive());
    }

    #[Test]
    public function it_tells_its_observers_when_it_changes_and_only_then(): void
    {
        $me = new Character(new PlayerName('lorekeeper03'));
        $watcher = new class() implements ObserverInterface {
            /** @var list<null|int> */
            public array $levels = [];

            #[Override]
            public function update(SubjectInterface $subject): void
            {
                $this->levels[] = $subject instanceof Character ? $subject->level()->value : null;
            }
        };

        $me->subscribe($watcher);
        $me->subscribe($watcher);
        $me->hear(new PlayersSeenEvent([self::seen('lorekeeper03', 4)]));
        $me->hear(new PlayersSeenEvent([self::seen('Someone', 90)]));
        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));
        $me->unsubscribe($watcher);
        $me->hear(new LeveledUpEvent(new PlayerLevel(6), new Experience(600)));

        static::assertSame([4, 5], $watcher->levels);
    }

    #[Test]
    public function it_does_not_know_where_it_is_before_entering_the_game(): void
    {
        $this->expectException(LogicException::class);

        new Character(new PlayerName('Hero'))->position();
    }

    private static function seen(string $name, int $level): SeenPlayer
    {
        return new SeenPlayer(
            new PlayerName($name),
            new PlayerLevel($level),
            new Cell('Enter'),
            Pad::Spawn,
            CombatState::Idle,
            new Health(390),
            new Health(390),
            new Mana(100),
            new Mana(100),
            false,
        );
    }
}
