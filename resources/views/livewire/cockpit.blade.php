<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Zuhause" icon="heroicon-o-home" />
    </x-slot>

    {{-- ZUHAUSE: das warme persönliche Cockpit. Oben die Begrüßung, darunter die
         präsente (aber begrenzte) Eingang-Box neben den Karten „zuletzt gestempelt",
         „offene Aufgaben" und „Termine heute". Der volle Posteingang lebt auf
         /eingang; hier ist er ein Fenster, kein Fass. --}}
    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-6xl px-6 py-7">

            {{-- Begrüßung + Check-in --}}
            <div class="mb-6 flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">
                        {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                    </h1>
                    <p class="mt-1 text-sm text-[color:var(--nx-muted)]">
                        {{ $dateLabel }}@if($streak > 0) · 🔥 {{ $streak }} {{ $streak === 1 ? 'Tag' : 'Tage' }} Streak@endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if(!$todayCheckin)
                        <x-nx-button variant="primary" x-data @click="$dispatch('open-modal-checkin')">
                            @svg('heroicon-o-sun', 'h-4 w-4') Check-in
                        </x-nx-button>
                    @else
                        <button type="button" x-data @click="$dispatch('open-modal-checkin')"
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                            @svg('heroicon-o-check-circle', 'h-4 w-4 text-[color:var(--nx-success)]') heute eingecheckt
                        </button>
                    @endif

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" title="Mehr"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                            @svg('heroicon-o-ellipsis-horizontal', 'h-5 w-5')
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.100ms
                             class="absolute right-0 z-20 mt-1 w-52 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
                            @foreach([
                                ['open-modal-team', 'heroicon-o-user-group', 'Team verwalten'],
                                ['open-modal-modules', 'heroicon-o-squares-2x2', 'Module verwalten'],
                                ['open-modal-user', 'heroicon-o-user-circle', 'Benutzer-Einstellungen'],
                            ] as [$event, $icon, $label])
                                <button type="button" x-data @click="open = false; $dispatch('{{ $event }}')"
                                        class="flex w-full items-center gap-2.5 px-3 py-1.5 text-left text-xs text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                                    @svg($icon, 'h-4 w-4 shrink-0 text-[color:var(--nx-muted)]') {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Raster: Eingang-Box (50%) neben den Karten --}}
            <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">

                {{-- ── Eingang: präsent, begrenzt, innen scrollbar ── --}}
                <section class="flex h-[500px] flex-col overflow-hidden rounded-[14px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] shadow-sm">
                    <header class="flex shrink-0 items-center justify-between gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-inbox', 'h-4 w-4 text-[color:var(--nx-muted)]')
                            Eingang
                            @if($eingangOpen > 0)
                                <span class="rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[11px] font-medium tabular-nums text-[color:var(--nx-muted)]">
                                    {{ $eingangOpen }}@if($eingangUnread > 0) · {{ $eingangUnread }} neu @endif
                                </span>
                            @endif
                        </h2>
                        <a href="{{ route('home.inbox') }}" wire:navigate
                           class="inline-flex items-center gap-1 text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">
                            Alle öffnen @svg('heroicon-o-arrow-right', 'h-3.5 w-3.5')
                        </a>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div class="divide-y divide-[color:var(--nx-line)]">
                            @forelse($items as $it)
                                @include('home::partials.inbox.card', ['it' => $it, 'dense' => true])
                            @empty
                                <div class="flex h-full items-center justify-center px-6 py-16">
                                    <x-nx-empty icon="heroicon-o-check-circle">Sauber — nichts Offenes im Eingang.</x-nx-empty>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                {{-- ── Rechte Spalte: Karten ── --}}
                <div class="flex flex-col gap-4">

                    {{-- Zuletzt gestempelt --}}
                    <section class="overflow-hidden rounded-[14px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] shadow-sm">
                        <header class="flex items-center justify-between gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                @svg('heroicon-o-clock', 'h-4 w-4 text-[color:var(--nx-muted)]')
                                Zuletzt gestempelt
                            </h2>
                            <a href="{{ route('home.zeiten') }}" wire:navigate class="text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">Zeiten</a>
                        </header>
                        <div class="px-4 py-3">
                            @if(!$timeAvailable)
                                <p class="text-xs text-[color:var(--nx-faint)]">Zeiterfassung nicht verbunden.</p>
                            @elseif($lastStamp)
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $lastStamp['context'] }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-[color:var(--nx-muted)]">
                                            <span class="font-medium text-[color:var(--nx-text)]">{{ $lastStamp['hours_label'] }}</span>
                                            <span>·</span>
                                            <span>{{ $lastStamp['when'] }}</span>
                                            @if($lastStamp['is_billed'])
                                                <span class="rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium">abgerechnet</span>
                                            @endif
                                        </div>
                                        @if($lastStamp['note'] !== '')
                                            <p class="mt-1 line-clamp-2 text-xs text-[color:var(--nx-faint)]">{{ $lastStamp['note'] }}</p>
                                        @endif
                                    </div>
                                    @svg('heroicon-o-clock', 'h-8 w-8 shrink-0 text-[color:var(--nx-accent-soft)]')
                                </div>
                            @else
                                <p class="text-xs text-[color:var(--nx-faint)]">Noch nichts gestempelt.</p>
                            @endif

                            @if($time7dLabel !== '')
                                <div class="mt-3 border-t border-[color:var(--nx-line)] pt-2 text-xs text-[color:var(--nx-muted)]">
                                    Letzte 7 Tage · <span class="font-medium tabular-nums text-[color:var(--nx-text)]">{{ $time7dLabel }}</span>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Offene Aufgaben --}}
                    <section class="overflow-hidden rounded-[14px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] shadow-sm">
                        <header class="flex items-center justify-between gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                @svg('heroicon-o-bolt', 'h-4 w-4 text-[color:var(--nx-muted)]')
                                Offene Aufgaben
                                @if($taskOverdue > 0)
                                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-danger,var(--nx-text))]">{{ $taskOverdue }} überfällig</span>
                                @elseif($taskFrogs > 0)
                                    <span class="rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">{{ $taskFrogs }} Frog{{ $taskFrogs === 1 ? '' : 's' }}</span>
                                @endif
                            </h2>
                            <a href="{{ route('home.aufgaben') }}" wire:navigate class="text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">
                                Alle{{ $taskTotal > 0 ? ' · ' . $taskTotal : '' }}
                            </a>
                        </header>

                        @if(!$tasksAvailable)
                            <div class="px-4 py-3"><p class="text-xs text-[color:var(--nx-faint)]">Aufgaben nicht verbunden.</p></div>
                        @elseif(empty($taskItems))
                            <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-check-circle">Keine offenen Aufgaben — stark.</x-nx-empty></div>
                        @else
                            <ul class="divide-y divide-[color:var(--nx-line)]">
                                @foreach($taskItems as $t)
                                    <li class="flex items-start gap-3 px-4 py-2.5">
                                        @svg(($t['is_frog'] ?? false) ? 'heroicon-o-fire' : 'heroicon-o-check-circle', 'mt-0.5 h-4 w-4 shrink-0 ' . (($t['is_overdue'] ?? false) ? 'text-[color:var(--nx-danger,var(--nx-faint))]' : 'text-[color:var(--nx-faint)]'))
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $t['title'] ?? 'Aufgabe' }}</div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-[color:var(--nx-faint)]">
                                                @if(!empty($t['project']))<span class="truncate">{{ $t['project'] }}</span>@endif
                                                @if(!empty($t['due_label']))
                                                    @if(!empty($t['project']))<span>·</span>@endif
                                                    <span class="{{ ($t['is_overdue'] ?? false) ? 'text-[color:var(--nx-danger,var(--nx-muted))]' : '' }}">{{ $t['due_label'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    {{-- Termine heute --}}
                    <section class="overflow-hidden rounded-[14px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] shadow-sm">
                        <header class="flex items-center justify-between gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                @svg('heroicon-o-calendar-days', 'h-4 w-4 text-[color:var(--nx-muted)]')
                                Termine heute
                            </h2>
                            <a href="{{ route('home.kalender') }}" wire:navigate class="text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">Kalender</a>
                        </header>

                        @if(!$calendarAvailable)
                            <div class="px-4 py-3"><p class="text-xs text-[color:var(--nx-faint)]">Kein Kalender-Connector verbunden.</p></div>
                        @elseif(empty($todayEvents))
                            <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-calendar">Keine Termine heute — freie Bahn.</x-nx-empty></div>
                        @else
                            <ul class="divide-y divide-[color:var(--nx-line)]">
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
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-success)]"></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                </div>
            </div>
        </div>
    </div>
</x-ui-page>
