@php
    $chip = 'display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .625rem; border-radius: .5rem; font-size: .75rem; font-weight: 500; cursor: pointer; border: 1px solid;';
    $off = 'background: var(--gray-50, #f9fafb); color: var(--gray-700, #374151); border-color: var(--gray-200, #e5e7eb);';
    $on = 'background: var(--primary-50, #eef2ff); color: var(--primary-700, #4338ca); border-color: var(--primary-300, #a5b4fc);';
    $warn = 'background: #fffbeb; color: #b45309; border-color: #fcd34d;';
@endphp

<div @if ($run->status->isActive()) wire:poll.3s @endif>
    <div style="display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-bottom: .75rem;">
        <div style="flex: 1 1 18rem; max-width: 28rem;">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input type="search" wire:model.live.debounce.300ms="search" placeholder="Search events or raw messages" />
            </x-filament::input.wrapper>
        </div>
        <span style="font-size: .75rem; color: var(--gray-500, #6b7280);">
            Showing {{ count($events) }} of {{ $total }}
        </span>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem;">
        <button type="button" wire:click="showAll" style="{{ $chip }} {{ $only === [] && trim($search) === '' ? $on : $off }}">
            All <span style="opacity: .7;">{{ $total }}</span>
        </button>
        @foreach ($counts as $name => $count)
            <button
                type="button"
                wire:key="chip-{{ $name }}"
                wire:click="toggle(@js($name))"
                style="{{ $chip }} {{ in_array($name, $only, true) ? $on : ($name === $unrecognized ? $warn : $off) }}"
            >
                {{ $name }} <span style="opacity: .7;">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    <div
        x-data="{ stick: true }"
        x-init="$el.scrollTop = $el.scrollHeight; new MutationObserver(() => { if (stick) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
        x-on:scroll="stick = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 24"
        style="max-height: 28rem; overflow-y: auto; padding: .75rem 1rem; border-radius: .75rem; background: #0b1020; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8125rem; line-height: 1.6;"
    >
        @forelse ($events as $event)
            <div wire:key="event-{{ $event->id }}" x-data="{ open: false }" style="display: flex; gap: 1rem; align-items: baseline;">
                <span style="color: #64748b; flex-shrink: 0;">{{ $event->created_at->format('H:i:s') }}</span>
                <span style="color: {{ $event->name === $unrecognized ? '#fbbf24' : ($event->name === 'Malformed' ? '#f87171' : '#a5b4fc') }}; flex-shrink: 0; min-width: 12rem;">{{ $event->name }}</span>
                @if ($event->raw === null)
                    <span style="color: #cbd5e1; word-break: break-word;">{{ $event->summary }}</span>
                @else
                    <div style="color: #cbd5e1; min-width: 0; flex: 1;">
                        <button type="button" x-on:click="open = ! open" style="color: inherit; cursor: pointer; background: none; border: 0; padding: 0; font: inherit;">
                            <span x-text="open ? '▾' : '▸'">▸</span> {{ $event->summary }}
                        </button>
                        <pre x-show="open" x-cloak style="margin: .25rem 0 .5rem; white-space: pre-wrap; word-break: break-all; color: #94a3b8;">{{ $event->raw }}</pre>
                    </div>
                @endif
            </div>
        @empty
            <div style="color: #64748b;">{{ $only === [] && trim($search) === '' ? 'No events yet…' : 'No events match the filter.' }}</div>
        @endforelse
    </div>
</div>
