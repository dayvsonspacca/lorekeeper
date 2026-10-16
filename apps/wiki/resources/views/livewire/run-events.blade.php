<div @if ($run->status->isActive()) wire:poll.3s @endif>
    <div style="display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem;">
        <x-filament::badge
            tag="button"
            wire:click="show(null)"
            :color="$only === null ? 'primary' : 'gray'"
        >
            All · {{ $total }}
        </x-filament::badge>
        @foreach ($counts as $name => $count)
            <x-filament::badge
                tag="button"
                wire:click="show(@js($name))"
                :color="$only === $name ? 'primary' : ($name === $unrecognized ? 'warning' : 'gray')"
            >
                {{ $name }} · {{ $count }}
            </x-filament::badge>
        @endforeach
    </div>

    <div
        x-data="{ stick: true }"
        x-init="$el.scrollTop = $el.scrollHeight; new MutationObserver(() => { if (stick) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
        x-on:scroll="stick = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 24"
        style="max-height: 28rem; overflow-y: auto; padding: .75rem 1rem; border-radius: .75rem; background: #0b1020; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8125rem; line-height: 1.6;"
    >
        @forelse ($events as $event)
            <div style="display: flex; gap: 1rem; align-items: baseline;">
                <span style="color: #64748b; flex-shrink: 0;">{{ $event->created_at->format('H:i:s') }}</span>
                <span style="color: {{ $event->name === $unrecognized ? '#fbbf24' : ($event->name === 'Malformed' ? '#f87171' : '#a5b4fc') }}; flex-shrink: 0; min-width: 12rem;">{{ $event->name }}</span>
                @if ($event->raw === null)
                    <span style="color: #cbd5e1; word-break: break-word;">{{ $event->summary }}</span>
                @else
                    <details style="color: #cbd5e1; min-width: 0; flex: 1;">
                        <summary style="cursor: pointer;">{{ $event->summary }}</summary>
                        <pre style="margin: .25rem 0 .5rem; white-space: pre-wrap; word-break: break-all; color: #94a3b8;">{{ $event->raw }}</pre>
                    </details>
                @endif
            </div>
        @empty
            <div style="color: #64748b;">No events yet…</div>
        @endforelse
    </div>
</div>
