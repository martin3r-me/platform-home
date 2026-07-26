<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Mein Kalender" icon="heroicon-o-calendar-days" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Zeitraum" icon="heroicon-o-adjustments-horizontal" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-6 p-4">
                <div class="flex flex-col gap-2">
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Vorschau</span>
                    <div class="flex flex-wrap gap-1">
                        <x-nx-button :variant="$daysAhead === 7 ? 'primary' : 'ghost'" size="sm" wire:click="setRange(7)">7 Tage</x-nx-button>
                        <x-nx-button :variant="$daysAhead === 14 ? 'primary' : 'ghost'" size="sm" wire:click="setRange(14)">14 Tage</x-nx-button>
                        <x-nx-button :variant="$daysAhead === 30 ? 'primary' : 'ghost'" size="sm" wire:click="setRange(30)">30 Tage</x-nx-button>
                    </div>
                </div>

                <div>
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Termine</span>
                    <div class="mt-1 space-y-0.5">
                        <x-nx-property-row icon="heroicon-o-calendar-days" label="Im Zeitraum">{{ $count }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-sun" label="Heute">{{ $todayCount }}</x-nx-property-row>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="mb-8 flex items-baseline justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">Mein Kalender</h1>
            <span class="text-sm text-[color:var(--nx-muted)] tabular-nums">{{ $count }} Termine</span>
        </div>

        @if(!$available)
            <x-nx-callout variant="neutral" title="Kein Kalender-Kontext">
                Der user-connectors-Dienst liefert deine synchronisierten Termine — hier ist er nicht verfügbar.
            </x-nx-callout>
        @elseif(empty($days))
            <x-nx-empty icon="heroicon-o-calendar-days">
                Keine Termine im gewählten Zeitraum.
            </x-nx-empty>
        @else
            <div class="space-y-8">
                @foreach($days as $day)
                    <x-nx-section :title="$day['label']">
                        <x-nx-card flush>
                            <ul class="divide-y divide-[color:var(--nx-line)]">
                                @foreach($day['entries'] as $e)
                                    <li @class(['opacity-60' => $e['is_past']])>
                                        <x-nx-list-item
                                            :icon="$e['is_online'] ? 'heroicon-o-video-camera' : 'heroicon-o-map-pin'"
                                            :title="$e['subject']"
                                            :subtitle="$e['location'] ?: $e['organizer']"
                                            :meta="$e['time_label']">
                                            @if($e['is_now'])
                                                <x-slot name="trailing">
                                                    <x-nx-badge variant="success">jetzt</x-nx-badge>
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
