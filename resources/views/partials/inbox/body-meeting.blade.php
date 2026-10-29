{{-- Meeting: Termin, Teilnehmer, Agenda --}}
<div class="space-y-4">
    {{-- Roter Faden: Termin → an Org-Knoten hängen → Zeiten-Tracking (automatisch) --}}
    @if($item['real'] ?? false)
        @php $isSeries = $item['is_series'] ?? false; $seriesN = $item['series_count'] ?? 1; @endphp
        <x-nx-callout variant="info" icon="heroicon-o-link" title="{{ $isSeries ? 'Serientermin an einen Knoten hängen' : 'Termin an einen Knoten hängen' }}">
            @if($isSeries)
                Häng die <strong>Serie</strong>{{ $seriesN > 1 ? ' (' . $seriesN . ' Vorkommen)' : '' }} im Panel
                <strong>Verknüpfungen</strong> an den passenden Org-Knoten — alle Vorkommen und künftige Termine
                docken automatisch an. Sobald er hängt, wird die Zeit pro Teilnehmer automatisch getrackt.
            @else
                Häng den Termin im Panel <strong>Verknüpfungen</strong> an den passenden Org-Knoten — danach wird
                die Zeit pro Teilnehmer automatisch getrackt.
            @endif
        </x-nx-callout>
    @endif

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

    @include('home::partials.inbox.transcript', ['segments' => $item['recording']['segments'] ?? [], 'label' => 'Aufnahme · Transcript'])

    @if(!empty($item['join_url']))
        <div>
            <x-nx-button variant="secondary" size="sm" :href="$item['join_url']">Teams öffnen</x-nx-button>
        </div>
    @endif
</div>
