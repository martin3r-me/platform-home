{{--
    Home – innere (kontextuelle) Sidebar, gerendert im x-slot "sidebar" der Views.
    Getrennt von der Haupt-Nav-Leiste (home.sidebar). Aktuell: Schnellzugriff.
--}}
<x-ui-page-sidebar title="Schnellzugriff" icon="heroicon-o-bolt" width="w-72" :defaultOpen="true" side="left">
    <div class="flex flex-col gap-2 p-4">
        <span class="px-1 pb-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Aktionen</span>
        <x-nx-button variant="secondary" class="w-full justify-start" x-data @click="$dispatch('open-modal-checkin')">
            @svg('heroicon-o-check-circle', 'w-4 h-4') Täglicher Check-in
        </x-nx-button>
        <x-nx-button variant="ghost" class="w-full justify-start" x-data @click="$dispatch('open-modal-team')">
            @svg('heroicon-o-user-group', 'w-4 h-4') Team verwalten
        </x-nx-button>
        <x-nx-button variant="ghost" class="w-full justify-start" x-data @click="$dispatch('open-modal-modules')">
            @svg('heroicon-o-squares-2x2', 'w-4 h-4') Module verwalten
        </x-nx-button>
        <x-nx-button variant="ghost" class="w-full justify-start" x-data @click="$dispatch('open-modal-user')">
            @svg('heroicon-o-user-circle', 'w-4 h-4') Benutzer-Einstellungen
        </x-nx-button>
    </div>
</x-ui-page-sidebar>
