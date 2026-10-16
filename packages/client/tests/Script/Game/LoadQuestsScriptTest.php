<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Game\LoadQuestsScript;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class LoadQuestsScriptTest extends TestCase
{
    #[Test]
    public function it_asks_in_batches_of_ten_with_a_pause_between_them(): void
    {
        $quests = array_map(static fn(int $id): QuestId => new QuestId($id), range(start: 1, end: 12));
        $loaded = new QuestLoadedEvent([], []);
        $timedOut = new TimedOut(new Await([QuestLoadedEvent::class], Duration::seconds(15)));

        [$instructions, $result] = Replay::run(
            new LoadQuestsScript(new AreaId(1350), $quests, Duration::seconds(2)),
            $loaded,
            null,
            $timedOut,
        );

        static::assertSame([$loaded, $timedOut], $result);
        static::assertCount(3, $instructions);
        static::assertInstanceOf(Send::class, $instructions[0]);
        static::assertSame('%xt%zm%getQuests%1350%1%2%3%4%5%6%7%8%9%10%', $instructions[0]->command->toMessage());
        static::assertEquals(new Pause(Duration::seconds(2)), $instructions[1]);
        static::assertInstanceOf(Send::class, $instructions[2]);
        static::assertSame('%xt%zm%getQuests%1350%11%12%', $instructions[2]->command->toMessage());
    }
}
