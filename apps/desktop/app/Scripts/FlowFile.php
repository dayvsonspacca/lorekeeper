<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psl\Type\Exception\CoercionException;

/** @mago-expect lint:cyclomatic-complexity */
final readonly class FlowFile
{
    public const int VERSION = 1;

    /** @throws InvalidFlowFile */
    public static function parse(string $json): Flow
    {
        try {
            $file = Type\json_decoded(Type\dict(Type\array_key(), Type\mixed()))->coerce($json);
        } catch (CoercionException) {
            throw new InvalidFlowFile(['the file must be JSON holding an object with a name, its nodes and its wires']);
        }

        return self::fromArray($file);
    }

    /**
     * @param array<array-key, mixed> $file
     *
     * @throws InvalidFlowFile
     */
    public static function fromArray(array $file): Flow
    {
        $top = new Fields($file, 'the file', ['lorekeeper', 'name', 'author', 'start', 'nodes', 'wires']);
        if (($file['lorekeeper'] ?? null) !== self::VERSION) {
            $top->problems[] = "the file: 'lorekeeper' must be " . self::VERSION;
        }

        $name = $top->text('name');
        $author = $top->optionalText('author');
        $start = $top->text('start');
        $problems = $top->problems;
        $nodes = self::nodes(self::listed($file, $problems), $problems);
        if ($start !== null && $nodes !== [] && !array_key_exists($start, $nodes)) {
            $problems[] = "the file: 'start' names no node ('{$start}')";
        }

        $wires = Wiring::read($file, $nodes, $problems);
        if ($problems !== [] || $name === null || $start === null || $nodes === []) {
            throw new InvalidFlowFile($problems === [] ? ['the file has no nodes'] : $problems);
        }

        return new Flow($name, $author, $start, array_values($nodes), $wires);
    }

    public static function encode(Flow $flow): string
    {
        return (
            json_encode(
                self::toArray($flow),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ) . "\n"
        );
    }

    /** @return array<string, mixed> */
    public static function toArray(Flow $flow): array
    {
        return [
            'lorekeeper' => self::VERSION,
            'name' => $flow->name,
            ...($flow->author === null ? [] : ['author' => $flow->author]),
            'start' => $flow->start,
            'nodes' => array_map(static fn(FlowNode $node): array => [
                'id' => $node->id,
                ...Steps::write($node->step),
                'at' => $node->at,
            ], $flow->nodes),
            'wires' => array_map(static fn(Wire $wire): array => [
                'from' => $wire->from,
                'exit' => $wire->exit,
                'to' => $wire->to,
            ], $flow->wires),
        ];
    }

    /**
     * @param array<array-key, mixed> $file
     * @param list<string> $problems
     *
     * @return list<array<array-key, mixed>>
     */
    private static function listed(array $file, array &$problems): array
    {
        try {
            return Type\non_empty_vec(Type\dict(Type\array_key(), Type\mixed()))->assert($file['nodes'] ?? null);
        } catch (AssertException) {
            $problems[] = "the file: 'nodes' must be a list of at least one object";

            return [];
        }
    }

    /**
     * @param list<array<array-key, mixed>> $listed
     * @param list<string> $problems
     *
     * @return array<non-empty-string, FlowNode>
     */
    private static function nodes(array $listed, array &$problems): array
    {
        $nodes = [];
        foreach ($listed as $index => $data) {
            $where = 'node ' . ($index + 1);
            $kind = Steps::kind($data['do'] ?? null);
            $id = new Fields($data, $where, array_keys($data))->text('id');
            if ($id === null || $kind === null) {
                $problems[] = $id === null
                    ? "{$where}: 'id' must be a text that is not empty"
                    : "{$where}: 'do' must be one of " . implode(', ', array_keys(Steps::KINDS));

                continue;
            }

            if (array_key_exists($id, $nodes)) {
                $problems[] = "{$where}: the id '{$id}' is taken by another node";

                continue;
            }

            $fields = new Fields($data, "node '{$id}' ({$kind})", ['id', 'do', 'at', ...Steps::KINDS[$kind]]);
            $step = Steps::read($kind, $fields);
            $at = $fields->position('at');
            array_push($problems, ...$fields->problems);
            if ($step !== null && $fields->problems === []) {
                $nodes[$id] = new FlowNode($id, $step, $at);
            }
        }

        return $nodes;
    }
}
