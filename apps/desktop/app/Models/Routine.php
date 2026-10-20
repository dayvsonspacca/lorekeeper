<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property array<array-key, array{script?: mixed, parameters?: mixed}> $tasks
 */
#[Fillable(['name', 'tasks'])]
final class Routine extends Model
{
    /** @return list<array{script: string, parameters: array<string, mixed>}> */
    public function steps(): array
    {
        $steps = [];
        foreach ($this->tasks as $task) {
            $script = $task['script'] ?? null;
            if (!is_string($script) || $script === '') {
                continue;
            }

            $parameters = $task['parameters'] ?? [];
            $steps[] = ['script' => $script, 'parameters' => is_array($parameters) ? $parameters : []];
        }

        return $steps;
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return ['tasks' => 'array'];
    }
}
