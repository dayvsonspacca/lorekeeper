<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\LevelUpScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class LevelUpRevivalTest extends TestCase
{
    #[Test]
    public function a_dead_character_resurrects_and_walks_back_to_the_monsters(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script(target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayerDiedEvent(),
            null,
            new ResurrectedEvent(),
            null,
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
            null,
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%oaklore%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
                '%xt%zm%resPlayerTimed%946%122628%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a4>p:122628%wvz%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
            ],
            self::sent($steps),
        );
        static::assertTrue($me->player()->vitals->alive());
    }

    #[Test]
    public function standing_again_answers_the_resurrection_as_well(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script(target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayerDiedEvent(),
            null,
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle),
            null,
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
            null,
        ]);

        static::assertContains('%xt%zm%resPlayerTimed%946%122628%', self::sent($steps));
        static::assertTrue($me->player()->vitals->alive());
    }

    #[Test]
    public function a_character_back_on_its_feet_before_asking_does_not_ask(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script(target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayerDiedEvent(),
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle),
            null,
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
            null,
        ]);

        static::assertNotContains('%xt%zm%resPlayerTimed%946%122628%', self::sent($steps));
        static::assertTrue($me->player()->vitals->alive());
    }

    private static function script(int $target): LevelUpScript
    {
        $now = Timestamp::fromParts(2026, 10, 16);

        return new LevelUpScript(new PlayerLevel($target), static function () use (&$now): Timestamp {
            return $now = $now->plus(Duration::seconds(3));
        });
    }

    /**
     * @param list<Instruction> $steps
     *
     * @return list<string>
     */
    private static function sent(array $steps): array
    {
        return array_values(array_map(
            static fn(Send $send): string => $send->command->toMessage(),
            array_filter($steps, static fn(Instruction $step): bool => $step instanceof Send),
        ));
    }
}
