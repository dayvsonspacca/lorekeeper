<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Scripts\Flow;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Lorekeeper\Apps\Desktop\Scripts\InvalidFlowFile;

/**
 * @property int $id
 * @property string $name
 * @property null|string $author
 * @property null|string $group
 * @property array{start?: null|string, nodes?: list<array<string, mixed>>, wires?: list<array<string, mixed>>, canvas?: array<string, mixed>} $drawing
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 */
#[Fillable(['name', 'author', 'group', 'drawing'])]
/** @mago-expect lint:cyclomatic-complexity */
final class Script extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['drawing' => '{"start":null,"nodes":[],"wires":[]}'];

    public static function fromFlow(Flow $flow): self
    {
        $file = FlowFile::toArray($flow);

        return new self([
            'name' => $flow->name,
            'author' => $flow->author,
            'group' => $flow->group,
            'drawing' => ['start' => $file['start'], 'nodes' => $file['nodes'], 'wires' => $file['wires']],
        ]);
    }

    /**
     * @param array<array-key, mixed> $drawing
     *
     * @return array{start: null|string, nodes: list<array<array-key, mixed>>, wires: list<array<array-key, mixed>>, canvas: array{start?: array{int, int}}}
     */
    public static function cleaned(array $drawing): array
    {
        $start = $drawing['start'] ?? null;
        $canvas = $drawing['canvas'] ?? null;
        $at = is_array($canvas) ? $canvas['start'] ?? null : null;

        return [
            'start' => is_string($start) && $start !== '' ? $start : null,
            'nodes' => self::objects($drawing['nodes'] ?? null),
            'wires' => self::objects($drawing['wires'] ?? null),
            'canvas' => is_array($at) && array_is_list($at) && count($at) === 2 && is_int($at[0]) && is_int($at[1])
                ? ['start' => [$at[0], $at[1]]]
                : [],
        ];
    }

    /** @return array<string, mixed> */
    public function file(): array
    {
        return [
            'lorekeeper' => FlowFile::VERSION,
            'name' => $this->name,
            ...($this->author === null ? [] : ['author' => $this->author]),
            ...($this->group === null ? [] : ['group' => $this->group]),
            'start' => $this->drawing['start'] ?? null,
            'nodes' => $this->drawing['nodes'] ?? [],
            'wires' => $this->drawing['wires'] ?? [],
        ];
    }

    /** @throws InvalidFlowFile */
    public function flow(): Flow
    {
        return FlowFile::fromArray($this->file());
    }

    /** @return list<string> */
    public function problems(): array
    {
        try {
            $this->flow();

            return [];
        } catch (InvalidFlowFile $invalid) {
            return $invalid->problems;
        }
    }

    /** @return array{nodes: array<string, list<string>>, others: list<string>, ready: bool} */
    public function review(): array
    {
        $nodes = [];
        $others = [];
        foreach ($this->problems() as $problem) {
            if (preg_match("/^node '(.+?)' \\([a-z-]+\\): (.+)$/", $problem, $match) === 1) {
                $nodes[$match[1]][] = $match[2];

                continue;
            }

            $others[] = $problem;
        }

        return ['nodes' => $nodes, 'others' => $others, 'ready' => $this->problems() === []];
    }

    public function nodes(): int
    {
        return count($this->drawing['nodes'] ?? []);
    }

    public function export(): string
    {
        return (
            json_encode(
                $this->file(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ) . "\n"
        );
    }

    /** @return list<array<array-key, mixed>> */
    private static function objects(mixed $listed): array
    {
        return is_array($listed) ? array_values(array_filter($listed, is_array(...))) : [];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['drawing' => 'array'];
    }
}
