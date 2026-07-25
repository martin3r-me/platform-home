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
                @php $hasContent = $hasTime || !empty($vitalSigns); @endphp
                @if(!$hasContent)
                    <x-nx-empty icon="heroicon-o-sparkles">
                        Alles ruhig — aktuell laufen keine Kennzahlen auf deinen Knoten auf.
                    </x-nx-empty>
                @else
                    {{-- Auf einen Blick — Kennzahlen, die dich brauchen --}}
                    @if(!empty($vitalSigns))
                        <x-nx-section icon="heroicon-o-squares-2x2" title="Auf einen Blick">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach($vitalSigns as $sectionKey => $metrics)
                                    @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                                    @foreach($metrics as $m)
                                        <x-nx-stat
                                            :label="$m['label'] ?? ''"
                                            :value="$m['value'] ?? 0"
                                            :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')"
                                            :accent="$this->accentFor($m['variant'] ?? 'default')"
                                            :href="$this->kpiHref($sectionKey)" />
                                    @endforeach
                                @endforeach
                            </div>
                        </x-nx-section>
                    @endif

                    {{-- Zeiten · 7 Tage — Balken (Orientierungshilfe) --}}
                    @if($hasTime)
                        <x-nx-card>
                            <div class="mb-3 flex items-baseline justify-between gap-3">
                                <div class="flex flex-wrap items-baseline gap-2">
                                    <span class="text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Zeiten · 7 Tage</span>
                                    <span class="text-lg font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $timeTotal }}</span>
                                    <span class="text-xs text-[color:var(--nx-faint)]">· abgerechnet {{ $timeBilled }}</span>
                                </div>
                                <x-nx-button variant="ghost" size="sm" :href="route('home.zeiten')" wire:navigate>Details</x-nx-button>
                            </div>
                            <div class="flex items-end gap-2" style="height: 84px;">
                                @foreach($timeByDay as $day)
                                    @php
                                        $barPx = $timeMaxMinutes > 0 ? max(3, (int) round($day['minutes'] / $timeMaxMinutes * 52)) : 3;
                                        $dayHours = $day['minutes'] > 0
                                            ? rtrim(rtrim(number_format($day['minutes'] / 60, 1, ',', ''), '0'), ',') . 'h'
                                            : '';
                                    @endphp
                                    <div class="flex flex-1 flex-col items-center justify-end gap-1">
                                        <span class="h-3 text-[10px] font-medium tabular-nums leading-none text-[color:var(--nx-muted)]">{{ $dayHours }}</span>
                                        <div class="w-full rounded-[3px] {{ $day['minutes'] > 0 ? 'bg-[color:var(--nx-accent)]' : 'bg-[color:var(--nx-accent-soft)]' }}" style="height: {{ $barPx }}px;"></div>
                                        <span class="text-[10px] uppercase text-[color:var(--nx-faint)]">{{ $day['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </x-nx-card>
                    @endif
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
