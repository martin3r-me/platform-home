{{-- Meeting: Termin, Teilnehmer, Agenda --}}
<div class="space-y-4">
    <x-nx-property-row icon="heroicon-o-clock" label="Wann">{{ $item['when'] ?? '—' }}</x-nx-property-row>

    <div>
        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Teilnehmer</div>
        <div class="flex flex-wrap gap-2">
            @foreach($item['participants'] ?? [] as $p)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[color:var(--nx-line)] py-1 pl-1 pr-2">
                    <x-nx-avatar :name="$p" size="sm" />
                    <span class="text-xs text-[color:var(--nx-text)]">{{ $p }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div>
        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Agenda</div>
        <ul class="space-y-1.5">
            @foreach($item['agenda'] ?? [] as $a)
                <li class="flex items-start gap-2 text-sm text-[color:var(--nx-text)]">
                    <span class="text-[color:var(--nx-faint)]">•</span>{{ $a }}
                </li>
            @endforeach
        </ul>
    </div>

    @if(!empty($item['recording']['segments']))
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Aufnahme · Transcript</div>
            <div class="space-y-2">
                @foreach($item['recording']['segments'] as $seg)
                    <div class="flex gap-3">
                        <span class="shrink-0 text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $seg[0] }}</span>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-[color:var(--nx-muted)]">{{ $seg[1] }}:</span>
                            <span class="text-sm text-[color:var(--nx-text)]">{{ $seg[2] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        @if(!empty($item['join_url']))
            <x-nx-button variant="secondary" size="sm" :href="$item['join_url']">Teams öffnen</x-nx-button>
        @endif
        <x-nx-button variant="ghost" size="sm">Protokoll</x-nx-button>
    </div>
</div>
