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

                {{-- Heute auf einen Blick (aus „Mein Tag" integriert) --}}
                <div>
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Heute</span>
                    <div class="mt-1 space-y-0.5">
                        <x-nx-property-row icon="heroicon-o-fire" label="Streak">{{ $streak > 0 ? $streak . ($streak === 1 ? ' Tag' : ' Tage') : '—' }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-check-circle" label="Check-in">{{ $todayCheckin ? 'erledigt' : 'offen' }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-clipboard-document-check" label="Offene To-dos">{{ count($openTodos) }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-calendar-days" label="Termine heute">{{ $todayEventCount }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-inbox" label="Eingang offen">{{ $eingangOpen }}</x-nx-property-row>
                    </div>
                </div>

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
                <p class="mt-1 text-sm text-[color:var(--nx-muted)]">{{ $dateLabel }}</p>
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
                    $moodLabels = \Platform\Core\Models\Checkin::getMoodScoreOptions();
                    $energyLabels = \Platform\Core\Models\Checkin::getEnergyScoreOptions();
                    $goal = trim((string) ($todayCheckin['daily_goal'] ?? ''));
                    $moodScore = $todayCheckin['mood_score'] ?? null;
                    $energyScore = $todayCheckin['energy_score'] ?? null;
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
                            @if($moodScore !== null || $energyScore !== null)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if($moodScore !== null)
                                        <x-nx-badge variant="neutral" dot>Stimmung: {{ $moodLabels[$moodScore] ?? $moodScore }}</x-nx-badge>
                                    @endif
                                    @if($energyScore !== null)
                                        <x-nx-badge variant="neutral" dot>Energie: {{ $energyLabels[$energyScore] ?? $energyScore }}</x-nx-badge>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-checkin')">
                            bearbeiten
                        </x-nx-button>
                    </div>
                </x-nx-card>
            @endif

            {{-- Heute im Fokus: Termine + offene To-dos --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {{-- Termine heute --}}
                <x-nx-card flush>
                    <div class="flex items-center justify-between px-4 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-calendar-days', 'w-4 h-4 text-[color:var(--nx-muted)]')
                            Termine heute
                        </h2>
                        <x-nx-button variant="ghost" size="sm" :href="route('home.kalender')" wire:navigate>Kalender</x-nx-button>
                    </div>

                    @if(!$calendarAvailable)
                        <div class="px-4 pb-4 text-xs text-[color:var(--nx-faint)]">Kein Kalender-Connector verbunden.</div>
                    @elseif(empty($todayEvents))
                        <x-nx-empty icon="heroicon-o-calendar">Keine Termine heute — freie Bahn.</x-nx-empty>
                    @else
                        <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                            @foreach($todayEvents as $e)
                                <li class="flex items-center gap-3 px-4 py-2.5 {{ ($e['is_past'] ?? false) ? 'opacity-60' : '' }}">
                                    <span class="w-14 shrink-0 text-xs font-medium tabular-nums text-[color:var(--nx-muted)]">{{ $e['time_label'] ?? '' }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $e['subject'] ?? 'Termin' }}</div>
                                        @if(!empty($e['location']) || !empty($e['is_online']))
                                            <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ !empty($e['is_online']) ? 'Online' : $e['location'] }}</div>
                                        @endif
                                    </div>
                                    @if(!empty($e['is_now']))
                                        <x-nx-badge variant="success" dot>jetzt</x-nx-badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-nx-card>

                {{-- Offene To-dos (Check-in) --}}
                <x-nx-card flush>
                    <div class="flex items-center justify-between px-4 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-clipboard-document-check', 'w-4 h-4 text-[color:var(--nx-muted)]')
                            Offene To-dos
                        </h2>
                        @if(count($openTodos) > 0)
                            <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ count($openTodos) }}</span>
                        @endif
                    </div>

                    @if(count($openTodos) === 0)
                        <x-nx-empty icon="heroicon-o-check-circle">Keine offenen To-dos — alles erledigt.</x-nx-empty>
                    @else
                        <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                            @foreach($openTodos as $todo)
                                <li>
                                    <button type="button" wire:click="toggleTodo({{ $todo['id'] }})"
                                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-[color:var(--nx-hover)]"
                                            aria-label="Als erledigt markieren">
                                        <span class="h-5 w-5 shrink-0 rounded-[6px] border border-[color:var(--nx-line-strong)]"></span>
                                        <span class="min-w-0 flex-1 text-sm text-[color:var(--nx-text)]">{{ $todo['title'] }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-nx-card>
            </div>

            {{-- Meine Aufgaben — Frogs zuerst --}}
            @if($tasksAvailable)
                <x-nx-card flush>
                    <div class="flex items-center justify-between px-4 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-bolt', 'w-4 h-4 text-[color:var(--nx-muted)]')
                            Meine Aufgaben
                            @if($taskOverdue > 0)
                                <x-nx-badge variant="danger">{{ $taskOverdue }} überfällig</x-nx-badge>
                            @elseif($taskFrogs > 0)
                                <x-nx-badge variant="accent">{{ $taskFrogs }} Frog{{ $taskFrogs === 1 ? '' : 's' }}</x-nx-badge>
                            @endif
                        </h2>
                        <x-nx-button variant="ghost" size="sm" :href="route('home.aufgaben')" wire:navigate>
                            Alle{{ $taskTotal > 0 ? ' · ' . $taskTotal : '' }}
                        </x-nx-button>
                    </div>

                    @if(empty($taskItems))
                        <x-nx-empty icon="heroicon-o-check-circle">Keine offenen Aufgaben — stark.</x-nx-empty>
                    @else
                        <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                            @foreach($taskItems as $t)
                                @include('home::partials.task-row', ['t' => $t])
                            @endforeach
                        </ul>
                    @endif
                </x-nx-card>
            @endif

            {{-- Auf einen Blick — Kennzahlen deines Knotens --}}
            @if($orgAvailable && $hasPerson && !empty($vitalSigns))
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

            {{-- Zeiten · 7 Tage --}}
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

            {{-- Ziele + Eingang --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {{-- Meine Ziele --}}
                @if($goalsAvailable)
                    <x-nx-card flush>
                        <div class="flex items-center justify-between px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                @svg('heroicon-o-flag', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                Meine Ziele
                                @if($goalAvgLabel)
                                    <span class="text-xs font-normal text-[color:var(--nx-faint)]">Ø {{ $goalAvgLabel }}</span>
                                @endif
                            </h2>
                            <x-nx-button variant="ghost" size="sm" :href="route('home.ziele')" wire:navigate>
                                Alle{{ $goalTotal > 0 ? ' · ' . $goalTotal : '' }}
                            </x-nx-button>
                        </div>

                        @if(empty($goalItems))
                            <x-nx-empty icon="heroicon-o-flag">Noch keine Ziele, für die du verantwortlich bist.</x-nx-empty>
                        @else
                            <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                                @foreach($goalItems as $o)
                                    <li class="flex items-center gap-3 px-4 py-2.5">
                                        @svg(($o['is_mountain'] ?? false) ? 'heroicon-o-trophy' : 'heroicon-o-flag', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $o['title'] ?? '' }}</div>
                                            @if(!empty($o['cycle']))
                                                <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ $o['cycle'] }}</div>
                                            @endif
                                        </div>
                                        @if(!empty($o['score_label']))
                                            <x-nx-badge :variant="$o['score_variant'] ?? 'neutral'">{{ $o['score_label'] }}</x-nx-badge>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-nx-card>
                @endif

                {{-- Eingang --}}
                @if($eingangAvailable)
                    <x-nx-card flush>
                        <div class="flex items-center justify-between px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                @svg('heroicon-o-inbox', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                Eingang
                                @if($eingangUnread > 0)
                                    <x-nx-badge variant="accent">{{ $eingangUnread }} neu</x-nx-badge>
                                @endif
                            </h2>
                            <x-nx-button variant="ghost" size="sm" :href="route('home.inbox')" wire:navigate>
                                Öffnen{{ $eingangOpen > 0 ? ' · ' . $eingangOpen : '' }}
                            </x-nx-button>
                        </div>

                        @if(empty($eingangTeaser))
                            <x-nx-empty icon="heroicon-o-inbox">Dein Eingang ist leer — sauber.</x-nx-empty>
                        @else
                            <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                                @foreach($eingangTeaser as $row)
                                    <li class="flex items-center gap-3 px-4 py-2.5">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-accent)]"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $row['label'] }}</div>
                                            @if($row['sub'] !== '')
                                                <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ $row['sub'] }}</div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-nx-card>
                @endif
            </div>

            {{-- Pflichtkurse-Hinweis, nur wenn überfällig --}}
            @if($courseOverdue > 0)
                <x-nx-callout variant="danger" title="Überfällige Pflichtkurse" icon="heroicon-o-academic-cap">
                    Du hast {{ $courseOverdue }} überfällige{{ $courseOverdue === 1 ? 'n Kurs' : ' Kurse' }}.
                    <x-slot name="action">
                        <x-nx-button variant="secondary" size="sm" :href="route('home.kurse')" wire:navigate>Zu meinen Kursen</x-nx-button>
                    </x-slot>
                </x-nx-callout>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
