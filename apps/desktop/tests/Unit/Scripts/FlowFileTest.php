<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scripts;

use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Lorekeeper\Apps\Desktop\Scripts\FlowNode;
use Lorekeeper\Apps\Desktop\Scripts\InvalidFlowFile;
use Lorekeeper\Apps\Desktop\Scripts\Steps;
use Lorekeeper\Apps\Desktop\Scripts\Wire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FlowFileTest extends TestCase
{
    #[Test]
    public function it_reads_the_nodes_where_they_were_drawn_and_the_wires_between_them(): void
    {
        $flow = FlowFile::parse(self::file('crypto-token-daily'));

        static::assertSame('Crypto Token daily', $flow->name);
        static::assertSame('Hilise', $flow->author);
        static::assertSame('enter', $flow->first()->id);
        static::assertSame([960, 120], $flow->node('hunt')?->at);
        static::assertSame(['yes', 'no'], $flow->node('done')?->exits());
        static::assertSame(['next', 'failed'], $flow->node('hunt')?->exits());
        static::assertEquals(new Wire('enter', 'next', 'done'), $flow->wires[0]);
        static::assertSame('turn-in', $flow->next('hunt', FlowNode::NEXT)?->id);
        static::assertSame('rest', $flow->next('hunt', FlowNode::FAILED)?->id);
        static::assertNull($flow->next('done', FlowNode::YES));
    }

    #[Test]
    public function it_reads_every_kind_of_node(): void
    {
        $flow = FlowFile::parse(self::file('every-node'));

        static::assertSame(
            [
                'Join boxes',
                'Move to Closet',
                'Hunt Sneevil in Closet for item 42658 ×1',
                'Fight in r3 until level 10',
                'Fight Carnivorous Cricket in r2 for 15 min',
                'Accept quest 6187',
                'Turn in quest 6187',
                'Turn in quest 6187 for item 42657',
                'Enhance the equipped gear with Lucky',
                'Rest',
                'Wait 30s',
                'Quest 6187 was already done',
                'Holding item 5586 ×20',
                'Level 30 or higher',
            ],
            array_map(static fn(FlowNode $node): string => $node->step->describe(), $flow->nodes),
        );
        static::assertSame([0, 0], $flow->first()->at);
        static::assertSame([-40, 120], $flow->node('b')?->at);
        static::assertSame([], $flow->wires);
    }

    #[Test]
    public function what_it_writes_reads_back_the_same(): void
    {
        foreach (['crypto-token-daily', 'every-node'] as $name) {
            $flow = FlowFile::parse(self::file($name));

            static::assertEquals($flow, FlowFile::parse(FlowFile::encode($flow)));
        }
    }

    #[Test]
    public function it_writes_every_wire_with_its_exit(): void
    {
        $flow = FlowFile::fromArray([
            'lorekeeper' => 1,
            'name' => 'Short',
            'start' => 'a',
            'nodes' => [
                ['id' => 'a', 'do' => 'move', 'cell' => 'Enter'],
                ['id' => 'b', 'do' => 'rest', 'at' => [9, 9]],
            ],
            'wires' => [['from' => 'a', 'to' => 'b']],
        ]);

        static::assertSame(<<<'JSON'
            {
                "lorekeeper": 1,
                "name": "Short",
                "start": "a",
                "nodes": [
                    {
                        "id": "a",
                        "do": "move",
                        "cell": "Enter",
                        "pad": "Left",
                        "at": [
                            0,
                            0
                        ]
                    },
                    {
                        "id": "b",
                        "do": "rest",
                        "at": [
                            9,
                            9
                        ]
                    }
                ],
                "wires": [
                    {
                        "from": "a",
                        "exit": "next",
                        "to": "b"
                    }
                ]
            }

            JSON, FlowFile::encode($flow));
    }

    /** @param list<string> $problems */
    #[Test]
    #[DataProvider('broken')]
    public function it_names_every_problem_of_a_broken_file(string $json, array $problems): void
    {
        try {
            FlowFile::parse($json);
            static::fail('the file was read');
        } catch (InvalidFlowFile $invalid) {
            static::assertSame($problems, $invalid->problems);
        }
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function broken(): iterable
    {
        $kinds = implode(', ', array_keys(Steps::KINDS));

        yield 'not json' => [
            '{"lorekeeper": 1,',
            [
                'the file must be JSON holding an object with a name, its nodes and its wires',
            ],
        ];
        yield 'empty object' => [
            '{}',
            [
                "the file: 'lorekeeper' must be 1",
                "the file: 'name' must be a text that is not empty",
                "the file: 'start' must be a text that is not empty",
                "the file: 'nodes' must be a list of at least one object",
            ],
        ];
        yield 'newer version, unknown field, wires not a list' => [
            '{"lorekeeper": 2, "name": "X", "author": "", "start": "a", "extra": 1, "nodes": [{"id": "a", "do": "rest"}], "wires": 3}',
            [
                "the file: unexpected field 'extra'",
                "the file: 'lorekeeper' must be 1",
                "the file: 'author' must be a text that is not empty",
                "the file: 'wires' must be a list of objects",
            ],
        ];
        yield 'nodes without an id, of an unknown kind or taken twice' => [
            '{"lorekeeper": 1, "name": "X", "start": "z", "nodes": [{"do": "rest"}, {"id": "a", "do": "dance"}, {"id": "b", "do": "rest"}, {"id": "b", "do": "rest"}]}',
            [
                "node 1: 'id' must be a text that is not empty",
                "node 2: 'do' must be one of {$kinds}",
                "node 4: the id 'b' is taken by another node",
                "the file: 'start' names no node ('z')",
            ],
        ];
        yield 'wrong fields' => [
            <<<'JSON'
                {"lorekeeper": 1, "name": "X", "start": "a", "nodes": [
                    {"id": "a", "do": "hunt", "cell": "", "monster": "", "item": 0, "quantity": 1, "minutes": 1, "at": [1]},
                    {"id": "b", "do": "level", "level": 101, "speed": 3},
                    {"id": "c", "do": "enhance", "type": "Unhandled"},
                    {"id": "d", "do": "move", "cell": "Enter", "pad": "Middle"}
                ]}
                JSON,
            [
                "node 'a' (hunt): 'cell' must be a text that is not empty",
                "node 'a' (hunt): 'monster' must be a text that is not empty",
                "node 'a' (hunt): 'item' must be a whole number from 1",
                "node 'a' (hunt): 'at' must be a pair of whole numbers, as in [120, -40]",
                "node 'b' (level): unexpected field 'speed'",
                "node 'b' (level): 'level' must be a whole number from 1 to 100",
                "node 'c' (enhance): 'type' must be one of Adventurer, Fighter, Thief, Hybrid, Wizard, Healer, SpellBreaker, Lucky",
                "node 'd' (move): 'pad' must be one of Left, Right, Top, Bottom, Up, Down, Center, Spawn",
            ],
        ];
        yield 'wrong wires' => [
            <<<'JSON'
                {"lorekeeper": 1, "name": "X", "start": "a", "nodes": [
                    {"id": "a", "do": "rest"}, {"id": "b", "do": "holding", "item": 1, "quantity": 1}
                ], "wires": [
                    {"from": "a"},
                    {"from": "x", "to": "a"},
                    {"from": "a", "to": "x"},
                    {"from": "b", "exit": "next", "to": "a"},
                    {"from": "a", "to": "b", "color": "red"},
                    {"from": "a", "to": "b"},
                    {"from": "a", "exit": "next", "to": "a"},
                    {"from": "b", "to": "b"}
                ]}
                JSON,
            [
                "wire 1: 'to' must be a text that is not empty",
                "wire 2: 'from' names no node ('x')",
                "wire 3: 'to' names no node ('x')",
                "wire 4: 'b' has no 'next' exit, only yes, no",
                "wire 5: unexpected field 'color'",
                "wire 7: the 'next' exit of 'a' is already wired",
            ],
        ];
    }

    private static function file(string $name): string
    {
        return (string) file_get_contents(__DIR__ . "/../../Fixtures/flows/{$name}.json");
    }
}
