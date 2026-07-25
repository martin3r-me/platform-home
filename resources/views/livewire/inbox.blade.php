<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- Links: Kanäle & Filter --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Kanäle" icon="heroicon-o-inbox" width="w-64" :defaultOpen="true" side="left">
            <div class="space-y-4 p-3">
                <x-ui-sidebar-list label="Eingang">
                    <x-ui-sidebar-item type="button" :active="true">
                        @svg('heroicon-o-inbox', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Alle</span>
                        <x-slot name="trailing"><span class="text-xs tabular-nums text-[color:var(--nx-faint)]">12</span></x-slot>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-envelope-open', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Ungelesen</span>
                        <x-slot name="trailing"><span class="text-xs tabular-nums text-[color:var(--nx-faint)]">5</span></x-slot>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-clock', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Snoozed</span>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-check-circle', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Erledigt</span>
                    </x-ui-sidebar-item>
                </x-ui-sidebar-list>

                <x-ui-sidebar-list label="Kanäle">
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-envelope', 'w-4 h-4 shrink-0')
                        <span class="text-sm">E-Mail</span>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-phone', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Anruf</span>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Teams</span>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-calendar-days', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Meeting</span>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-microphone', 'w-4 h-4 shrink-0')
                        <span class="text-sm">Aufnahme</span>
                    </x-ui-sidebar-item>
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-bell', 'w-4 h-4 shrink-0')
                        <span class="text-sm">System</span>
                    </x-ui-sidebar-item>
                </x-ui-sidebar-list>

                <x-ui-sidebar-list label="Fokus">
                    <x-ui-sidebar-item type="button">
                        @svg('heroicon-o-star', 'w-4 h-4 shrink-0')
                        <span class="text-sm">VIP</span>
                    </x-ui-sidebar-item>
                </x-ui-sidebar-list>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Rechts: Item-Liste --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Eingang" icon="heroicon-o-inbox" width="w-80" :defaultOpen="true" side="right" storeKey="activityOpen">
            <ul class="divide-y divide-[color:var(--nx-line)]">
                @foreach (range(1, 7) as $i)
                    <li class="flex items-start gap-3 px-4 py-3">
                        <div class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-[color:var(--nx-hover)]"></div>
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="h-3 w-24 rounded bg-[color:var(--nx-hover)]"></div>
                                <div class="h-2.5 w-10 rounded bg-[color:var(--nx-hover)]"></div>
                            </div>
                            <div class="h-3 w-40 rounded bg-[color:var(--nx-hover)]"></div>
                            <div class="h-2.5 w-full rounded bg-[color:var(--nx-hover)]"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Mitte: Reading-Pane (Struktur angedeutet) --}}
    <x-ui-page-container width="contained">
        <div class="space-y-6">
            {{-- Header --}}
            <div class="space-y-2">
                <div class="h-5 w-2/3 rounded bg-[color:var(--nx-hover)]"></div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="h-3 w-28 rounded bg-[color:var(--nx-hover)]"></div>
                    <div class="h-3 w-16 rounded bg-[color:var(--nx-hover)]"></div>
                    <x-nx-badge variant="neutral">Kanal</x-nx-badge>
                </div>
            </div>

            {{-- Action-Bar (die Inbox-Superkraft) --}}
            <div class="flex flex-wrap gap-2">
                <x-nx-button variant="primary" size="sm" disabled>Erledigt</x-nx-button>
                <x-nx-button variant="secondary" size="sm" disabled>Snooze</x-nx-button>
                <x-nx-button variant="secondary" size="sm" disabled>→ Aufgabe</x-nx-button>
                <x-nx-button variant="secondary" size="sm" disabled>→ Ticket</x-nx-button>
                <x-nx-button variant="secondary" size="sm" disabled>An Knoten</x-nx-button>
                <x-nx-button variant="secondary" size="sm" disabled>Antworten</x-nx-button>
            </div>

            {{-- KI-Zusammenfassung --}}
            <x-nx-callout variant="info" title="KI-Zusammenfassung">
                Hier landet die automatische Zusammenfassung + extrahierte Aktionen des ausgewählten Items.
            </x-nx-callout>

            {{-- Body / Transcript --}}
            <div class="space-y-2">
                <div class="h-3 w-full rounded bg-[color:var(--nx-hover)]"></div>
                <div class="h-3 w-11/12 rounded bg-[color:var(--nx-hover)]"></div>
                <div class="h-3 w-4/5 rounded bg-[color:var(--nx-hover)]"></div>
                <div class="h-3 w-3/4 rounded bg-[color:var(--nx-hover)]"></div>
                <div class="h-3 w-2/3 rounded bg-[color:var(--nx-hover)]"></div>
            </div>

            <p class="pt-2 text-xs text-[color:var(--nx-faint)]">
                Layout-Skelett · Inhalte folgen über den Inbox-Kontrakt (Liste, Reading-Pane, Aktionen).
            </p>
        </div>
    </x-ui-page-container>
</x-ui-page>
