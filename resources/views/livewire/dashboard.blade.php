<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-squares-2x2" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Kontext" icon="heroicon-o-identification" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-6 p-4">
                @if($hasPerson)
                    <div class="flex items-center gap-3">
                        <x-nx-avatar :name="$personName ?: $firstName" :src="$avatar" size="lg" />
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $personName ?: $firstName }}</div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Verantwortlich · dein Knoten</div>
                        </div>
                    </div>
                @endif

                @include('home::partials.quick-actions')
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        {{-- Kopf --}}
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">
                    {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                </h1>
                <p class="mt-1 text-sm text-[color:var(--nx-muted)]">Dein Überblick — auf einen Blick.</p>
            </div>
            @if($streak > 0)
                <x-nx-badge variant="accent">🔥 {{ $streak }} {{ $streak === 1 ? 'Tag' : 'Tage' }} Streak</x-nx-badge>
            @endif
        </div>

        <div class="space-y-8">
            {{-- Täglicher Check-in --}}
            @if(!$todayCheckin)
                <x-nx-callout variant="warning" title="Noch kein Check-in heute" icon="heroicon-o-sun">
                    Starte deinen Tag mit einem kurzen Check-in — Ziel setzen, Stimmung festhalten.
                    <x-slot name="action">
                        <x-nx-button variant="primary" x-data @click="$dispatch('open-modal-checkin')">
                            Jetzt einchecken
                        </x-nx-button>
                    </x-slot>
                </x-nx-callout>
            @else
                @php
                    $goal = trim((string) ($todayCheckin['daily_goal'] ?? ''));
                @endphp
                <x-nx-card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-[color:var(--nx-success)]')
                                Heute eingecheckt
                            </div>
                            <div class="mt-1 text-[color:var(--nx-text)]">
                                {{ $goal !== '' ? $goal : '— kein Tagesziel gesetzt —' }}
                            </div>
                        </div>
                        <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-checkin')">
                            bearbeiten
                        </x-nx-button>
                    </div>
                </x-nx-card>
            @endif

            {{-- Auf einen Blick — Kennzahlen, die dich brauchen --}}
            @if(!$orgAvailable)
                <x-nx-callout variant="neutral" title="Kein Organisations-Kontext">
                    Das organization-Modul liefert deine Kennzahlen — hier ist es nicht verfügbar.
                </x-nx-callout>
            @elseif(!$hasPerson)
                <x-nx-callout variant="info" title="Noch kein Person-Knoten verknüpft">
                    Sobald dein Benutzer im organization-Modul mit einer Person-Entität verknüpft ist, laufen hier deine Kennzahlen zusammen.
                </x-nx-callout>
            @else
                @php $hasKpis = $hasTime || !empty($vitalSigns); @endphp
                @if($hasKpis)
                    <x-nx-section icon="heroicon-o-squares-2x2" title="Auf einen Blick">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @if($hasTime)
                                <x-nx-stat label="Zeiten · 7 Tage" :value="$timeTotal" icon="heroicon-o-clock" :href="route('home.zeiten')" />
                            @endif
                            @foreach($vitalSigns as $sectionKey => $metrics)
                                @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                                @foreach($metrics as $m)
                                    <x-nx-stat
                                        :label="$m['label'] ?? ''"
                                        :value="$m['value'] ?? 0"
                                        :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')"
                                        :accent="$this->accentFor($m['variant'] ?? 'default')" />
                                @endforeach
                            @endforeach
                        </div>
                    </x-nx-section>
                @else
                    <x-nx-empty icon="heroicon-o-sparkles">
                        Alles ruhig — aktuell laufen keine Kennzahlen auf deinen Knoten auf.
                    </x-nx-empty>
                @endif
            @endif

            {{-- Changes & Prozesse — Beteiligung folgt über die Graph-Policy (kompakter Platzhalter) --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-nx-card>
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-arrows-right-left', 'w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                        <span class="text-sm font-semibold text-[color:var(--nx-text)]">Changes</span>
                        <span class="ml-auto"><x-nx-badge variant="neutral">bald</x-nx-badge></span>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-[color:var(--nx-faint)]">
                        Welche Transformation an deinen Einheiten gerade ansteht — folgt über die Graph-Beteiligung.
                    </p>
                </x-nx-card>
                <x-nx-card>
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-share', 'w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                        <span class="text-sm font-semibold text-[color:var(--nx-text)]">Prozesse</span>
                        <span class="ml-auto"><x-nx-badge variant="neutral">bald</x-nx-badge></span>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-[color:var(--nx-faint)]">
                        Abläufe, an denen du beteiligt bist, und dein nächster Schritt — folgt über die Graph-Beteiligung.
                    </p>
                </x-nx-card>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
