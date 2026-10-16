<div @if ($run->status->isActive()) wire:poll.2s="refreshRun" @endif>
    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: .75rem; font-size: .875rem;">
        <x-filament::badge :color="$run->status->getColor()" :icon="$run->status->getIcon()">
            {{ $run->status->getLabel() }}
        </x-filament::badge>
        @if ($run->duration() !== null)
            <span style="color: var(--gray-500);">{{ $run->duration() }}</span>
        @endif
        @if ($run->status->isActive())
            <span style="color: var(--gray-500);">updating live</span>
        @endif
    </div>

    <div
        x-data="{ stick: true }"
        x-init="$el.scrollTop = $el.scrollHeight; new MutationObserver(() => { if (stick) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
        x-on:scroll="stick = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 24"
        style="max-height: 28rem; overflow-y: auto; padding: .75rem 1rem; border-radius: .75rem; background: #0b1020; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8125rem; line-height: 1.6;"
    >
        @forelse ($lines as $line)
            <div style="display: flex; gap: 1rem; white-space: pre-wrap; word-break: break-word;">
                <span style="color: #64748b; flex-shrink: 0;">{{ $line['at'] }}</span>
                <span style="color: #475569; flex-shrink: 0; min-width: 2.5rem;">{{ $line['task'] === null ? '' : '#' . $line['task'] }}</span>
                <span style="color: {{ match ($line['level']) {
                    'success' => '#86efac',
                    'warning' => '#fbbf24',
                    'error' => '#f87171',
                    default => '#cbd5e1',
                } }};">{{ $line['message'] }}</span>
            </div>
        @empty
            <div style="color: #64748b;">Waiting for the bot to start…</div>
        @endforelse
    </div>
</div>
