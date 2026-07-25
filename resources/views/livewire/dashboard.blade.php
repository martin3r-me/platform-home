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
        <div class="mb-10 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">
                    {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                </h1>
                <p class="mt-1 text-sm text-[color:var(--nx-muted)]">Dein Überblick</p>
            </div>
            @if($streak > 0)
                <x-nx-badge variant="accent">🔥 {{ $streak }} {{ $streak === 1 ? 'Tag' : 'Tage' }} Streak</x-nx-badge>
            @endif
        </div>

        <div class="space-y-10">
            {{-- Täglicher Check-in (Core, unabhängig vom org-Kontext) --}}
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
                    $moodLabels = \Platform\Core\Models\Checkin::getMoodScoreOptions();
                    $energyLabels = \Platform\Core\Models\Checkin::getEnergyScoreOptions();
                    $goal = trim((string) ($todayCheckin['daily_goal'] ?? ''));
                    $moodScore = $todayCheckin['mood_score'] ?? null;
                    $energyScore = $todayCheckin['energy_score'] ?? null;
                @endphp
                <x-nx-card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                            @svg('heroicon-o-check-circle', 'w-4 h-4 text-[color:var(--nx-success)]')
                            Heute eingecheckt
                        </div>
                        <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-checkin')">
                            bearbeiten
                        </x-nx-button>
                    </div>

                    <div class="mt-3">
                        <div class="text-xs text-[color:var(--nx-faint)]">Tagesziel</div>
                        <div class="mt-0.5 text-[color:var(--nx-text)]">
                            {{ $goal !== '' ? $goal : '— kein Ziel gesetzt —' }}
                        </div>
                    </div>

                    @if($moodScore !== null || $energyScore !== null)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($moodScore !== null)
                                <x-nx-badge variant="neutral" dot>Stimmung: {{ $moodLabels[$moodScore] ?? $moodScore }}</x-nx-badge>
                            @endif
                            @if($energyScore !== null)
                                <x-nx-badge variant="neutral" dot>Energie: {{ $energyLabels[$energyScore] ?? $energyScore }}</x-nx-badge>
                            @endif
                        </div>
                    @endif
                </x-nx-card>
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
                        Kotter-Change-Prozesse an deinen Einheiten — welche Transformation gerade ansteht, mit Phase &amp; Fortschritt. Erscheint, sobald die Beteiligung über den Graphen steht.
                    </p>
                </x-nx-card>
                <x-nx-card>
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-share', 'w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                        <span class="text-sm font-semibold text-[color:var(--nx-text)]">Prozesse</span>
                        <span class="ml-auto"><x-nx-badge variant="neutral">bald</x-nx-badge></span>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-[color:var(--nx-faint)]">
                        Abläufe, an denen du beteiligt bist — laufende Durchläufe und dein nächster Schritt. Erscheint, sobald die Beteiligung über den Graphen steht.
                    </p>
                </x-nx-card>
            </div>

            {{-- Org-gespeiste Inhalte --}}
            @if(!$orgAvailable)
                <x-nx-callout variant="neutral" title="Kein Organisations-Kontext">
                    Das organization-Modul ist hier nicht verfügbar — es liefert den Person-Knoten, aus dem Kennzahlen und Zuständigkeiten speisen.
                </x-nx-callout>
            @else
                {{-- Getrackte Zeiten (letzte 7 Tage) --}}
                @if($hasTime)
                    <x-nx-section icon="heroicon-o-clock" title="Zeiten" :hint="$timeTotal" description="Letzte 7 Tage · gestempelt">
                        <x-nx-card>
                            <div class="mb-4 flex items-baseline gap-3">
                                <span class="text-2xl font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $timeTotal }}</span>
                                <span class="text-xs text-[color:var(--nx-faint)]">davon abgerechnet {{ $timeBilled }}</span>
                            </div>
                            <div class="flex items-end gap-2" style="height: 72px;">
                                @foreach($timeByDay as $day)
                                    @php
                                        $barPx = $timeMaxMinutes > 0 ? max(3, (int) round($day['minutes'] / $timeMaxMinutes * 56)) : 3;
                                    @endphp
                                    <div class="flex flex-1 flex-col items-center justify-end gap-1">
                                        <span class="text-[10px] tabular-nums {{ $day['minutes'] > 0 ? 'text-[color:var(--nx-muted)]' : 'text-[color:var(--nx-faint)]' }}">{{ $day['minutes'] > 0 ? $day['hours'] : '' }}</span>
                                        <div class="w-full rounded-[3px] {{ $day['minutes'] > 0 ? 'bg-[color:var(--nx-accent)]' : 'bg-[color:var(--nx-accent-soft)]' }}" style="height: {{ $barPx }}px;"></div>
                                        <span class="text-[10px] uppercase text-[color:var(--nx-faint)]">{{ $day['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </x-nx-card>
                    </x-nx-section>
                @endif

                @if(!$hasPerson)
                    <x-nx-callout variant="info" title="Noch kein Person-Knoten verknüpft">
                        Sobald dein Benutzer im organization-Modul mit einer Person-Entität verknüpft ist, laufen hier auch deine Aufgaben, Ziele und Kennzahlen zusammen.
                    </x-nx-callout>
                @elseif(empty($vitalSigns) && empty($responsibilities))
                    @unless($hasTime)
                        <x-nx-empty icon="heroicon-o-sparkles">
                            Alles ruhig — aktuell laufen keine Kennzahlen oder Zuständigkeiten auf deinen Knoten auf.
                        </x-nx-empty>
                    @endunless
                @else
                    {{-- Vital Signs --}}
                    @foreach($vitalSigns as $sectionKey => $metrics)
                        @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                        <x-nx-section
                            :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')"
                            :title="$cfg['label'] ?? ucfirst($sectionKey)"
                            :description="$cfg['description'] ?? null">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach($metrics as $m)
                                    <x-nx-stat
                                        :label="$m['label'] ?? ($m['key'] ?? '')"
                                        :value="$m['value'] ?? 0"
                                        :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')"
                                        :accent="$this->accentFor($m['variant'] ?? 'default')" />
                                @endforeach
                            </div>
                        </x-nx-section>
                    @endforeach

                    {{-- Zuständigkeiten --}}
                    @foreach($responsibilities as $sectionKey => $groups)
                        @php
                            $cfg = $sectionConfigs[$sectionKey] ?? [];
                            $sectionTotal = 0;
                            foreach ($groups as $g) { $sectionTotal += $g['total_count'] ?? count($g['items'] ?? []); }
                        @endphp
                        <x-nx-section
                            :icon="'heroicon-o-' . ($cfg['icon'] ?? 'queue-list')"
                            :title="$cfg['label'] ?? ucfirst($sectionKey)"
                            :hint="$sectionTotal ?: null"
                            :description="$cfg['description'] ?? null">
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                @foreach($groups as $group)
                                    @php
                                        $items = $group['items'] ?? [];
                                        $total = $group['total_count'] ?? count($items);
                                        $more  = max(0, $total - count($items));
                                    @endphp
                                    <x-nx-card flush>
                                        <div class="flex items-center justify-between gap-2 px-4 py-3">
                                            <div class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                                @svg('heroicon-o-' . ($group['icon'] ?? 'folder'), 'w-4 h-4 text-[color:var(--nx-muted)]')
                                                {{ $group['label'] ?? '' }}
                                            </div>
                                            <x-nx-badge variant="neutral">{{ $total }}</x-nx-badge>
                                        </div>

                                        @if(empty($items))
                                            <div class="px-4 pb-3 text-xs text-[color:var(--nx-faint)]">Nichts offen.</div>
                                        @else
                                            <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                                                @foreach($items as $item)
                                                    <li>
                                                        <x-nx-list-item
                                                            :title="$item['name'] ?? '—'"
                                                            :meta="$item['meta'] ?? null"
                                                            :href="$item['url'] ?? null" />
                                                    </li>
                                                @endforeach
                                            </ul>
                                            @if($more > 0)
                                                <div class="border-t border-[color:var(--nx-line)] px-4 py-2 text-xs text-[color:var(--nx-faint)]">
                                                    +{{ $more }} weitere
                                                </div>
                                            @endif
                                        @endif
                                    </x-nx-card>
                                @endforeach
                            </div>
                        </x-nx-section>
                    @endforeach
                @endif
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
