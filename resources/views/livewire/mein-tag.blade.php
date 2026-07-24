<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Mein Tag" icon="heroicon-o-sun" />
    </x-slot>

    <x-slot name="sidebar">
        @include('home::partials.sidebar')
    </x-slot>

    <x-ui-page-container width="contained">
        {{-- Begrüßung --}}
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-[color:var(--nx-text)]">
                    {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                </h1>
                <p class="mt-1 text-sm text-[color:var(--nx-muted)]">
                    {{ \Illuminate\Support\Carbon::now()->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                </p>
            </div>
            @if($streak > 0)
                <x-nx-badge variant="accent">🔥 {{ $streak }} {{ $streak === 1 ? 'Tag' : 'Tage' }} Streak</x-nx-badge>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Heute: Check-in --}}
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

            {{-- Offene Todos --}}
            <x-nx-card flush>
                <div class="flex items-center justify-between px-4 py-3">
                    <h2 class="text-sm font-semibold text-[color:var(--nx-text)]">Offene Todos</h2>
                    @if(count($openTodos) > 0)
                        <span class="text-xs text-[color:var(--nx-faint)] tabular-nums">{{ count($openTodos) }}</span>
                    @endif
                </div>

                @if(count($openTodos) === 0)
                    <x-nx-empty icon="heroicon-o-check-circle">
                        Keine offenen Todos — alles erledigt.
                    </x-nx-empty>
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
    </x-ui-page-container>
</x-ui-page>
