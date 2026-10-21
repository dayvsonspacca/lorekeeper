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
 * @property array{start?: null|string, nodes?: list<array<string, mixed>>, wires?: list<array<string, mixed>>} $drawing
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 */
#[Fillable(['name', 'author', 'drawing'])]
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
            'drawing' => ['start' => $file['start'], 'nodes' => $file['nodes'], 'wires' => $file['wires']],
        ]);
    }

    /** @return array<string, mixed> */
    public function file(): array
    {
        return [
            'lorekeeper' => FlowFile::VERSION,
            'name' => $this->name,
            ...($this->author === null ? [] : ['author' => $this->author]),
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['drawing' => 'array'];
    }
}
