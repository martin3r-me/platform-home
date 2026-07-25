{{-- Anruf: Anrufdaten --}}
<div class="space-y-4">
    <div class="space-y-0.5">
        <x-nx-property-row icon="heroicon-o-arrow-down-left" label="Richtung">{{ $item['direction_label'] ?? '—' }}</x-nx-property-row>
        <x-nx-property-row icon="heroicon-o-clock" label="Dauer">{{ $item['call_duration'] ?? '—' }}</x-nx-property-row>
        <x-nx-property-row icon="heroicon-o-phone" label="Nummer">{{ $item['number'] ?? '—' }}</x-nx-property-row>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-nx-button variant="primary" size="sm">Rückruf</x-nx-button>
        <x-nx-button variant="ghost" size="sm">Als Aufgabe</x-nx-button>
    </div>
</div>
