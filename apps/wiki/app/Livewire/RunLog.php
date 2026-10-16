<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunLog;

final class RunLog extends Component
{
    private const int LINES = 500;

    public BotRun $run;

    public string $seen = '';

    public function mount(): void
    {
        $this->seen = $this->signature();
    }

    public function refreshRun(): void
    {
        $this->run->refresh();

        $signature = $this->signature();
        if ($signature === $this->seen) {
            return;
        }

        $this->seen = $signature;
        $this->dispatch('run-changed');
    }

    private function signature(): string
    {
        return implode('|', [
            $this->run->status->value,
            $this->run->started_at?->toIso8601String(),
            $this->run->finished_at?->toIso8601String(),
            $this->run->result,
        ]);
    }

    public function render(): View
    {
        $lines = $this->run
            ->logs()
            ->latest('id')
            ->limit(self::LINES)
            ->get()
            ->reverse()
            ->map(static fn(BotRunLog $log): array => [
                'at' => $log->created_at->format('H:i:s'),
                'message' => $log->message,
                'level' => $log->level->value,
            ])
            ->values()
            ->all();

        return view('livewire.run-log', ['lines' => $lines]);
    }
}
