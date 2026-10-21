<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scripts;

use InvalidArgumentException;
use Lorekeeper\Apps\Desktop\Scripts\Flow;
use Lorekeeper\Apps\Desktop\Scripts\FlowNode;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Bot\Steps\Pauses\Rest;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class FlowScriptTest extends TestCase
{
    #[Test]
    public function it_is_titled_after_the_flow(): void
    {
        static::assertSame('Branches', self::branches()->script()->title());
    }

    #[Test]
    public function a_check_goes_out_of_the_exit_its_answer_names(): void
    {
        $entered = [];
        $script = self::branches()
            ->script(entering: static function (FlowNode $node) use (&$entered): void {
                $entered[] = $node->id;
            });

        [$instructions, $result] = Flows::play($script, self::me(level: 10), [null]);

        static::assertSame(['level', 'short'], $entered);
        static::assertEquals([new Pause(Duration::seconds(5))], $instructions);
        static::assertEquals(new Done('ended after: Wait 5s'), $result);
        static::assertEquals(
            [new Pause(Duration::seconds(30))],
            Flows::play(self::branches()->script(), self::me(level: 40), [null])[0],
        );
    }

    #[Test]
    public function an_action_that_fails_with_no_failed_wire_stops_the_script(): void
    {
        [, $result] = Flows::play(
            Flows::flow([
                ['id' => 'accept', 'do' => 'accept-quest', 'quest' => 6187],
            ])->script(),
            self::me(),
            [null],
        );

        static::assertEquals(new Failed('Accept quest 6187: the game would not give the quest'), $result);
    }

    #[Test]
    public function an_action_that_fails_goes_out_of_its_failed_wire(): void
    {
        [$instructions, $result] = Flows::play(
            Flows::flow([
                ['id' => 'accept', 'do' => 'accept-quest', 'quest' => 6187],
                ['id' => 'wait', 'do' => 'wait', 'seconds' => 5],
            ], [['from' => 'accept', 'exit' => 'failed', 'to' => 'wait']])->script(),
            self::me(),
            [null, null],
        );

        static::assertEquals(new Pause(Duration::seconds(5)), $instructions[1]);
        static::assertEquals(new Done('ended after: Wait 5s'), $result);
    }

    #[Test]
    public function what_the_game_breaks_off_counts_as_a_failure_of_the_node(): void
    {
        [, $result] = Flows::play(
            Flows::flow([['id' => 'enter', 'do' => 'join', 'map' => 'boxes']])->script(),
            self::me(),
            [
                new TimedOut(new Await([MovedToMapEvent::class], Duration::seconds(15))),
            ],
        );

        static::assertEquals(new Failed('Join boxes: Could not join boxes: no answer'), $result);
    }

    #[Test]
    public function a_loop_that_never_talks_to_the_game_slows_down_instead_of_spinning(): void
    {
        $flow = Flows::flow([
            ['id' => 'held', 'do' => 'holding', 'item' => 5586, 'quantity' => 1],
            ['id' => 'level', 'do' => 'level', 'level' => 1],
        ], [['from' => 'held', 'exit' => 'no', 'to' => 'level'], ['from' => 'level', 'exit' => 'yes', 'to' => 'held']]);

        $running = $flow->script()->run(self::me());

        static::assertEquals(new Pause(Duration::seconds(1)), $running->current());
    }

    #[Test]
    public function a_flow_must_start_at_one_of_its_nodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The flow starts at 'b', which is not one of its nodes.");

        new Flow('Lost', null, 'b', [new FlowNode('a', new Rest())]);
    }

    private static function branches(): Flow
    {
        return Flows::flow(
            [
                ['id' => 'level', 'do' => 'level', 'level' => 30],
                ['id' => 'long', 'do' => 'wait', 'seconds' => 30],
                ['id' => 'short', 'do' => 'wait', 'seconds' => 5],
            ],
            [
                ['from' => 'level', 'exit' => 'yes', 'to' => 'long'],
                ['from' => 'level', 'exit' => 'no', 'to' => 'short'],
            ],
            name: 'Branches',
        );
    }

    private static function me(int $level = 10): Character
    {
        return Characters::me('lorekeeper03', $level, 'battleon');
    }
}
