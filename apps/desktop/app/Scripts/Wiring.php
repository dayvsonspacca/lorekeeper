<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Psl\Type;
use Psl\Type\Exception\AssertException;

/** @mago-expect lint:cyclomatic-complexity */
final readonly class Wiring
{
    /**
     * @param array<array-key, mixed> $file
     * @param array<non-empty-string, FlowNode> $nodes
     * @param list<string> $problems
     *
     * @return list<Wire>
     */
    public static function read(array $file, array $nodes, array &$problems): array
    {
        try {
            $listed = Type\vec(Type\dict(Type\array_key(), Type\mixed()))->assert($file['wires'] ?? []);
        } catch (AssertException) {
            $problems[] = "the file: 'wires' must be a list of objects";

            return [];
        }

        $wires = [];
        foreach ($listed as $index => $data) {
            $where = 'wire ' . ($index + 1);
            $wire = self::wire($data, $where, $nodes, $problems);
            if ($wire === null) {
                continue;
            }

            if (!self::taken($wires, $wire)) {
                $wires[] = $wire;

                continue;
            }

            $problems[] = "{$where}: the '{$wire->exit}' exit of '{$wire->from}' is already wired";
        }

        return $wires;
    }

    /** @param list<Wire> $wires */
    private static function taken(array $wires, Wire $wire): bool
    {
        foreach ($wires as $taken) {
            if ($taken->from === $wire->from && $taken->exit === $wire->exit) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<non-empty-string, FlowNode> $nodes
     * @param list<string> $problems
     */
    private static function wire(array $data, string $where, array $nodes, array &$problems): ?Wire
    {
        $fields = new Fields($data, $where, ['from', 'exit', 'to']);
        $from = $fields->text('from');
        $to = $fields->text('to');
        $exit = $fields->optionalText('exit');
        $problems = [...$problems, ...$fields->problems];
        if ($fields->problems !== [] || $from === null || $to === null) {
            return null;
        }

        $source = $nodes[$from] ?? null;
        $target = $nodes[$to] ?? null;
        if ($source === null || $target === null) {
            $problems[] =
                "{$where}: '"
                . ($source === null ? 'from' : 'to')
                . "' names no node ('"
                . ($source === null ? $from : $to)
                . "')";

            return null;
        }

        $exit ??= $source->exits()[0];
        if (!in_array($exit, $source->exits(), strict: true)) {
            $problems[] = "{$where}: '{$from}' has no '{$exit}' exit, only " . implode(', ', $source->exits());

            return null;
        }

        return new Wire($from, $exit, $to);
    }
}
