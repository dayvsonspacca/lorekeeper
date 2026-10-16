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

    public string $status = '';

    public function mount(): void
    {
        $this->status = $this->run->status->value;
    }

    public function refreshRun(): void
    {
        $this->run->refresh();

        if ($this->run->status->value === $this->status) {
            return;
        }

        $this->status = $this->run->status->value;
        $this->dispatch('run-changed');
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
                'tone' => self::tone($log->message),
            ])
            ->values()
            ->all();

        return view('livewire.run-log', ['lines' => $lines]);
    }

    private static function tone(string $message): string
    {
        return match (true) {
            str_starts_with($message, '= failed'), str_starts_with($message, '? ') => 'danger',
            str_starts_with($message, '= disconnected'), str_contains($message, 'timed out') => 'warning',
            str_starts_with($message, '= ') => 'final',
            str_starts_with($message, '… ') => 'quiet',
            default => 'step',
        };
    }
}
