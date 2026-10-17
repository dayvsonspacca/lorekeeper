<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Models\BotTask;

final readonly class TaskStats
{
    public function __construct(
        private BotTask $task,
        private Carbon $now,
    ) {}

    public function level(): ?int
    {
        return $this->task->level_to ?? $this->task->level_from;
    }

    public function target(): ?int
    {
        if ($this->task->script !== BotScript::LevelUp) {
            return null;
        }

        return Parameters::level($this->task->parameters)->value;
    }

    public function levelsGained(): int
    {
        $level = $this->level();
        if ($level === null || $this->task->level_from === null) {
            return 0;
        }

        return max(0, $level - $this->task->level_from);
    }

    public function levelsLeft(): ?int
    {
        $target = $this->target();
        $level = $this->level();
        if ($target === null || $level === null) {
            return null;
        }

        return max(0, $target - $level);
    }

    public function minutes(): float
    {
        if ($this->task->started_at === null) {
            return 0.0;
        }

        return max(0.0, $this->task->started_at->diffInSeconds($this->task->finished_at ?? $this->now) / 60);
    }

    public function killsPerMinute(): ?float
    {
        $minutes = $this->minutes();

        return $minutes < 0.5 ? null : round($this->task->kills / $minutes, precision: 1);
    }

    public function minutesPerLevel(): ?float
    {
        $gained = $this->levelsGained();

        return $gained === 0 ? null : round($this->minutes() / $gained, precision: 1);
    }

    public function minutesToTarget(): ?float
    {
        $perLevel = $this->minutesPerLevel();
        $left = $this->levelsLeft();

        return $perLevel === null || $left === null ? null : round($perLevel * $left);
    }
}
