<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meine Zeiten" icon="heroicon-o-clock" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Zeitraum" icon="heroicon-o-adjustments-horizontal" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-6 p-4">
                <div class="flex flex-col gap-2">
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Zeitraum</span>
                    <div class="flex flex-wrap gap-1">
                        <x-nx-button :variant="$period === '7' ? 'primary' : 'ghost'" size="sm" wire:click="setPeriod('7')">7 Tage</x-nx-button>
                        <x-nx-button :variant="$period === '30' ? 'primary' : 'ghost'" size="sm" wire:click="setPeriod('30')">30 Tage</x-nx-button>
                        <x-nx-button :variant="$period === 'month' ? 'primary' : 'ghost'" size="sm" wire:click="setPeriod('month')">Monat</x-nx-button>
                    </div>
                </div>

                <div>
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Summe</span>
                    <div class="mt-1 space-y-0.5">
                        <x-nx-property-row icon="heroicon-o-clock" label="Gesamt">{{ $total }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-check-circle" label="Abgerechnet">{{ $billed }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-ellipsis-horizontal-circle" label="Offen">{{ $open }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-banknotes" label="Betrag">{{ $amount }}</x-nx-property-row>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="mb-8 flex items-baseline justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">Meine Zeiten</h1>
            <span class="text-sm text-[color:var(--nx-muted)] tabular-nums">{{ $total }} · {{ $entryCount }} Einträge</span>
        </div>

        @if($missingCount > 0)
            <x-nx-callout variant="warning" title="Tage ohne erfasste Zeit" icon="heroicon-o-exclamation-triangle" class="mb-6">
                {{ $missingCount }} Werktag(e) ohne Zeiterfassung: {{ $missingLabel }}
            </x-nx-callout>
        @endif

        @if(!$available)
            <x-nx-callout variant="neutral" title="Kein Organisations-Kontext">
                Das organization-Modul liefert die Zeiten — hier ist es nicht verfügbar.
            </x-nx-callout>
        @elseif(empty($days))
            <x-nx-empty icon="heroicon-o-clock">
                Keine gestempelten Zeiten im gewählten Zeitraum.
            </x-nx-empty>
        @else
            <div class="space-y-8">
                @foreach($days as $day)
                    <x-nx-section :title="$day['label']" :hint="$day['hours']">
                        <x-nx-card flush>
                            <ul class="divide-y divide-[color:var(--nx-line)]">
                                @foreach($day['entries'] as $e)
                                    <li>
                                        <x-nx-list-item
                                            icon="heroicon-o-clock"
                                            :title="$e['note'] ?: ($e['context'] ?: 'Zeiteintrag')"
                                            :subtitle="$e['note'] ? $e['context'] : null"
                                            :meta="$e['hours']">
                                            @if($e['is_billed'])
                                                <x-slot name="trailing">
                                                    <x-nx-badge variant="success">abgerechnet</x-nx-badge>
                                                </x-slot>
                                            @endif
                                        </x-nx-list-item>
                                    </li>
                                @endforeach
                            </ul>
                        </x-nx-card>
                    </x-nx-section>
                @endforeach
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
