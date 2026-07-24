{{--
    Home – gemeinsame Haupt-Navigation (in allen Views via
    @include('home::partials.sidebar') in den sidebar-Slot eingebunden).
    Shell-Komponenten (x-ui-*), bereits nx-getönt. Aktiver Zustand via routeIs.
--}}
<x-ui-page-sidebar title="Home" icon="heroicon-o-home" width="w-64" :defaultOpen="true" side="left">
    <div class="p-3 space-y-4">
        <x-ui-sidebar-list label="Übersicht">
            <x-ui-sidebar-item :href="route('home.dashboard')" :active="request()->routeIs('home.dashboard')">
                @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                <span class="ml-2 text-sm">Dashboard</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.mein-tag')" :active="request()->routeIs('home.mein-tag')">
                @svg('heroicon-o-sun', 'w-4 h-4')
                <span class="ml-2 text-sm">Mein Tag</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.agenda')" :active="request()->routeIs('home.agenda')">
                @svg('heroicon-o-queue-list', 'w-4 h-4')
                <span class="ml-2 text-sm">Agenda</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.checkin')" :active="request()->routeIs('home.checkin')">
                @svg('heroicon-o-check-circle', 'w-4 h-4')
                <span class="ml-2 text-sm">Checkin</span>
            </x-ui-sidebar-item>
        </x-ui-sidebar-list>

        <x-ui-sidebar-list label="Meins">
            <x-ui-sidebar-item :href="route('home.kalender')" :active="request()->routeIs('home.kalender')">
                @svg('heroicon-o-calendar-days', 'w-4 h-4')
                <span class="ml-2 text-sm">Kalender</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.aufgaben')" :active="request()->routeIs('home.aufgaben')">
                @svg('heroicon-o-clipboard-document-check', 'w-4 h-4')
                <span class="ml-2 text-sm">Meine Aufgaben</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.ziele')" :active="request()->routeIs('home.ziele')">
                @svg('heroicon-o-flag', 'w-4 h-4')
                <span class="ml-2 text-sm">Meine Ziele</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.notizen')" :active="request()->routeIs('home.notizen')">
                @svg('heroicon-o-pencil-square', 'w-4 h-4')
                <span class="ml-2 text-sm">Notizen</span>
            </x-ui-sidebar-item>
        </x-ui-sidebar-list>

        <x-ui-sidebar-list label="Konto">
            <x-ui-sidebar-item :href="route('home.benachrichtigungen')" :active="request()->routeIs('home.benachrichtigungen')">
                @svg('heroicon-o-bell', 'w-4 h-4')
                <span class="ml-2 text-sm">Benachrichtigungen</span>
            </x-ui-sidebar-item>
            <x-ui-sidebar-item :href="route('home.profil')" :active="request()->routeIs('home.profil')">
                @svg('heroicon-o-user-circle', 'w-4 h-4')
                <span class="ml-2 text-sm">Profil & Einstellungen</span>
            </x-ui-sidebar-item>
        </x-ui-sidebar-list>
    </div>
</x-ui-page-sidebar>
