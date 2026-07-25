{{-- Anruf: Anrufdaten --}}
<div class="space-y-4">
    <div class="space-y-0.5">
        <x-nx-property-row icon="heroicon-o-arrow-down-left" label="Richtung">{{ $item['direction_label'] ?? '—' }}</x-nx-property-row>
        <x-nx-property-row icon="heroicon-o-clock" label="Dauer">{{ $item['call_duration'] ?? '—' }}</x-nx-property-row>
        <x-nx-property-row icon="heroicon-o-phone" label="Nummer">{{ $item['number'] ?? '—' }}</x-nx-property-row>
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
        <x-nx-button variant="primary" size="sm">Als Aufgabe</x-nx-button>
    </div>
</div>
