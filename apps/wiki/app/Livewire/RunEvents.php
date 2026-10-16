<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunEvent;

final class RunEvents extends Component
{
    private const int EVENTS = 300;

    public BotRun $run;

    public ?string $only = null;

    public function show(?string $name): void
    {
        $this->only = $name === $this->only ? null : $name;
    }

    public function render(): View
    {
        $this->run->refresh();

        /** @var array<string, int> $counts */
        $counts = $this->run
            ->events()
            ->selectRaw('name, count(*) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->pluck('total', 'name')
            ->all();

        $events = $this->run
            ->events()
            ->when($this->only !== null, fn($query) => $query->where('name', $this->only))
            ->latest('id')
            ->limit(self::EVENTS)
            ->get()
            ->reverse()
            ->values()
            ->all();

        return view('livewire.run-events', [
            'counts' => $counts,
            'events' => $events,
            'total' => array_sum($counts),
            'unrecognized' => BotRunEvent::UNRECOGNIZED,
        ]);
    }
}
