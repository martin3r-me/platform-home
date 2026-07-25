<div>
    {{-- Modul-Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic uppercase text-[color:var(--nx-muted)] border-b border-[color:var(--nx-line)] mb-2">
        Home
    </div>

    <x-ui-sidebar-list label="Übersicht">
        <x-ui-sidebar-item :href="route('home.dashboard')" :active="request()->routeIs('home.dashboard')">
            @svg('heroicon-o-squares-2x2', 'w-4 h-4 shrink-0')
            <span class="text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.inbox')" :active="request()->routeIs('home.inbox')">
            @svg('heroicon-o-inbox', 'w-4 h-4 shrink-0')
            <span class="text-sm">Eingang</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.mein-tag')" :active="request()->routeIs('home.mein-tag')">
            @svg('heroicon-o-sun', 'w-4 h-4 shrink-0')
            <span class="text-sm">Mein Tag</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.agenda')" :active="request()->routeIs('home.agenda')">
            @svg('heroicon-o-queue-list', 'w-4 h-4 shrink-0')
            <span class="text-sm">Agenda</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.checkin')" :active="request()->routeIs('home.checkin')">
            @svg('heroicon-o-check-circle', 'w-4 h-4 shrink-0')
            <span class="text-sm">Checkin</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    <x-ui-sidebar-list label="Meins">
        <x-ui-sidebar-item :href="route('home.kalender')" :active="request()->routeIs('home.kalender')">
            @svg('heroicon-o-calendar-days', 'w-4 h-4 shrink-0')
            <span class="text-sm">Kalender</span>
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
        <x-ui-sidebar-item :href="route('home.notizen')" :active="request()->routeIs('home.notizen')">
            @svg('heroicon-o-pencil-square', 'w-4 h-4 shrink-0')
            <span class="text-sm">Notizen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    <x-ui-sidebar-list label="Konto">
        <x-ui-sidebar-item :href="route('home.benachrichtigungen')" :active="request()->routeIs('home.benachrichtigungen')">
            @svg('heroicon-o-bell', 'w-4 h-4 shrink-0')
            <span class="text-sm">Benachrichtigungen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('home.profil')" :active="request()->routeIs('home.profil')">
            @svg('heroicon-o-user-circle', 'w-4 h-4 shrink-0')
            <span class="text-sm">Profil & Einstellungen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    @foreach($moduleGroups as $group)
        <x-ui-sidebar-list :label="$group['label']">
            @foreach($group['modules'] as $mod)
                <x-ui-sidebar-item :href="$mod['url']">
                    @svg($mod['icon'], 'w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                    <span class="text-sm">{{ $mod['title'] }}</span>
                </x-ui-sidebar-item>
            @endforeach
        </x-ui-sidebar-list>
    @endforeach
</div>
