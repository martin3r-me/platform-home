{{-- Widget: Meine Ziele (okr PersonObjectiveSummary). --}}
<x-home::widget title="Meine Ziele" icon="heroicon-o-flag"
    :href="route('home.ziele')" :action="'Alle' . ($goalTotal > 0 ? ' · ' . $goalTotal : '')"
    :badge="$goalAvgLabel ? 'Ø ' . $goalAvgLabel : null">

    @if(!$goalsAvailable)
        <div class="px-4 py-3"><p class="text-xs text-[color:var(--nx-faint)]">Ziele nicht verbunden.</p></div>
    @elseif(empty($goalItems))
        <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-flag">Noch keine Ziele, für die du verantwortlich bist.</x-nx-empty></div>
    @else
        <ul class="divide-y divide-[color:var(--nx-line)]">
            @foreach($goalItems as $o)
                <li class="flex items-center gap-3 px-4 py-2.5">
                    @svg(($o['is_mountain'] ?? false) ? 'heroicon-o-trophy' : 'heroicon-o-flag', 'h-4 w-4 shrink-0 text-[color:var(--nx-faint)]')
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $o['title'] ?? 'Ziel' }}</div>
                        @if(!empty($o['cycle']))
                            <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ $o['cycle'] }}</div>
                        @endif
                    </div>
                    @if(!empty($o['score_label']))
                        <x-nx-badge :variant="$o['score_variant'] ?? 'neutral'">{{ $o['score_label'] }}</x-nx-badge>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-home::widget>
