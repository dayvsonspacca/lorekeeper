@props(['lines', 'now'])
<ul {{ $attributes->class('lk-feed') }}>
    @forelse ($lines as $line)
        <li wire:key="feed-{{ $line['at'] }}-{{ md5($line['text']) }}" style="--tone: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::feed($line['kind']) }};">
            <span class="lk-feed__icon">
                <x-scene.glyph :name="match ($line['kind']) {
                    'defeated' => 'swords',
                    'died' => 'skull',
                    'revived' => 'revive',
                    'leveled-up' => 'star',
                    'ranked-up' => 'crown',
                    default => 'pin',
                }" />
            </span>
            <span class="lk-feed__text">
                {{ $line['text'] }}
                @if ($line['detail'] !== null)
                    <small>{{ $line['detail'] }}</small>
                @endif
            </span>
            <span class="lk-feed__at">{{ \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($line['at'], $now) }}</span>
        </li>
    @empty
        <li class="lk-dim" style="font-size: .8rem; border: 0;">Nothing happened yet. The first fight shows up here.</li>
    @endforelse
</ul>
