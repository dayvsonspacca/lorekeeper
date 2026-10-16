<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunEvent;

final class RunEvents extends Component
{
    private const int EVENTS = 300;

    public BotRun $run;

    /** @var list<string> */
    public array $only = [];

    public string $search = '';

    public function toggle(string $name): void
    {
        $this->only = in_array($name, $this->only, strict: true)
            ? array_values(array_diff($this->only, [$name]))
            : [...$this->only, $name];
    }

    public function showAll(): void
    {
        $this->only = [];
        $this->search = '';
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

        $search = trim($this->search);
        $events = $this->run
            ->events()
            ->when($this->only !== [], fn(Builder $query): Builder => $query->whereIn('name', $this->only))
            ->when($search
            !== '', static fn(Builder $query): Builder => $query->where(static fn(Builder $matches): Builder => $matches->where(
                'summary',
                'like',
                "%{$search}%",
            )->orWhere('raw', 'like', "%{$search}%")))
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
