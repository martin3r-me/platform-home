<div>
    {{-- Home-Sidebar = die PERSÖNLICHE Navigation. Kein Modul-Launcher mehr:
         zwischen Modulen wird oben in der Topbar gewechselt (Modul-Flyout +
         Favoriten + „Alle Module"). Hier nur das eigene Zuhause. --}}
    <x-ui-sidebar-list label="Start">
        <x-ui-sidebar-item :href="route('home.dashboard')" :active="request()->routeIs('home.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 shrink-0')
            <span class="text-sm">Zuhause</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.inbox')" :active="request()->routeIs('home.inbox')">
            @svg('heroicon-o-inbox', 'w-4 h-4 shrink-0')
            <span class="text-sm">Eingang</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    <x-ui-sidebar-list label="Meins">
        <x-ui-sidebar-item :href="route('home.kalender')" :active="request()->routeIs('home.kalender')">
            @svg('heroicon-o-calendar-days', 'w-4 h-4 shrink-0')
            <span class="text-sm">Kalender</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.agenda')" :active="request()->routeIs('home.agenda*')">
            @svg('heroicon-o-queue-list', 'w-4 h-4 shrink-0')
            <span class="text-sm">Agenda</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.zeiten')" :active="request()->routeIs('home.zeiten')">
            @svg('heroicon-o-clock', 'w-4 h-4 shrink-0')
            <span class="text-sm">Meine Zeiten</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.aufgaben')" :active="request()->routeIs('home.aufgaben')">
            @svg('heroicon-o-clipboard-document-check', 'w-4 h-4 shrink-0')
            <span class="text-sm">Meine Aufgaben</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.ziele')" :active="request()->routeIs('home.ziele')">
            @svg('heroicon-o-flag', 'w-4 h-4 shrink-0')
            <span class="text-sm">Meine Ziele</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.kurse')" :active="request()->routeIs('home.kurse')">
            @svg('heroicon-o-academic-cap', 'w-4 h-4 shrink-0')
            <span class="text-sm">Meine Kurse</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>
</div>
