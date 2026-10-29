<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- COCKPIT: ein schlanker Kopf (Begrüßung + Tages-Glance + Schnellaktionen),
         darunter der vollbreite Triage-Strom. Keine innere Sidebar — die ganze
         Fläche gehört dem Postfach. Ein Klick auf eine Zeile öffnet die Item-View. --}}
    <div class="flex min-h-0 flex-1 flex-col">

        {{-- Kopf: Begrüßung, Datum/Streak, Tages-Glance, Schnellaktionen --}}
        <div class="shrink-0 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-6 pt-6 pb-4">
            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold tracking-tight text-[color:var(--nx-text)]">
                        {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                    </h1>
                    <p class="mt-0.5 text-sm text-[color:var(--nx-muted)]">
                        {{ $dateLabel }}@if($streak > 0) · 🔥 {{ $streak }} {{ $streak === 1 ? 'Tag' : 'Tage' }}@endif
                    </p>
                </div>

                {{-- Schnellaktionen: Check-in primär, Rest hinter ⋯ --}}
                <div class="flex items-center gap-2">
                    @if(!$todayCheckin)
                        <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-checkin')">
                            @svg('heroicon-o-sun', 'h-4 w-4') Check-in
                        </x-nx-button>
                    @else
                        <button type="button" x-data @click="$dispatch('open-modal-checkin')"
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                            @svg('heroicon-o-check-circle', 'h-4 w-4 text-[color:var(--nx-success)]') eingecheckt
                        </button>
                    @endif

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" title="Mehr"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
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

            {{-- Tages-Glance: wenige klickbare Pillen statt einer ganzen Sidebar --}}
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @php
                    $pill = 'inline-flex items-center gap-1.5 rounded-full border border-[color:var(--nx-line)] px-3 py-1 text-xs font-medium transition-colors';
                @endphp

                <span class="{{ $pill }} text-[color:var(--nx-text)]">
                    @svg('heroicon-o-inbox', 'h-3.5 w-3.5 text-[color:var(--nx-muted)]')
                    {{ $eingangOpen }} im Eingang @if($eingangUnread > 0)<span class="text-[color:var(--nx-accent)]">· {{ $eingangUnread }} neu</span>@endif
                </span>

                @if($calendarAvailable)
                    <a href="{{ route('home.kalender') }}" wire:navigate class="{{ $pill }} text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                        @svg('heroicon-o-calendar-days', 'h-3.5 w-3.5')
                        {{ $todayEventCount }} {{ $todayEventCount === 1 ? 'Termin' : 'Termine' }} heute
                    </a>
                @endif

                @if($tasksAvailable && ($taskOverdue > 0 || $taskFrogs > 0))
                    <a href="{{ route('home.aufgaben') }}" wire:navigate class="{{ $pill }} {{ $taskOverdue > 0 ? 'border-[color:var(--nx-danger,var(--nx-line))] text-[color:var(--nx-danger,var(--nx-text))]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]' }}">
                        @svg('heroicon-o-bolt', 'h-3.5 w-3.5')
                        @if($taskOverdue > 0){{ $taskOverdue }} überfällig @else{{ $taskFrogs }} Frog{{ $taskFrogs === 1 ? '' : 's' }}@endif
                    </a>
                @endif
            </div>
        </div>

        {{-- Filterleiste: Kanal × Status, kompakt & ruhig, volle Breite --}}
        <div class="shrink-0 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
            <div class="flex flex-wrap items-center gap-1 px-6 py-2">
                @foreach($channels as $c)
                    <button type="button" wire:click="setChannel('{{ $c['key'] }}')" title="{{ $c['label'] }}"
                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium transition-colors {{ $channel === $c['key'] ? 'bg-[color:var(--nx-active)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">
                        @svg($c['icon'], 'h-3.5 w-3.5 shrink-0')
                        {{ $c['label'] }}
                        @if($c['count'])
                            <span class="tabular-nums text-[color:var(--nx-faint)]">{{ $c['count'] }}</span>
                        @endif
                    </button>
                @endforeach
                <span class="mx-1 h-4 w-px bg-[color:var(--nx-line)]"></span>
                @foreach($statuses as $s)
                    <button type="button" wire:click="setStatus('{{ $s['key'] }}')"
                            class="rounded-md px-2 py-1 text-xs font-medium transition-colors {{ $status === $s['key'] ? 'bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">
                        {{ $s['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Massenaktion: erscheint, sobald etwas ausgewählt ist --}}
        @if(count($selected) > 0)
            <div class="shrink-0 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-accent-soft)]">
                <div class="flex flex-wrap items-center gap-2 px-6 py-2">
                    <span class="text-sm font-semibold text-[color:var(--nx-text)]">{{ count($selected) }} ausgewählt</span>

                    <button type="button" wire:click="selectAll" class="text-xs font-medium text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">Alle auswählen</button>
                    <button type="button" wire:click="clearSelection" class="text-xs font-medium text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">Aufheben</button>

                    <span class="mx-1 h-4 w-px bg-[color:var(--nx-line)]"></span>

                    <button type="button" wire:click="bulkDone" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-text)] transition-colors hover:border-[color:var(--nx-success)] hover:text-[color:var(--nx-success)]">
                        @svg('heroicon-o-check', 'h-3.5 w-3.5') Erledigt
                    </button>

                    <button type="button" wire:click="bulkIgnore" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-text)] transition-colors hover:border-[color:var(--nx-danger,var(--nx-line))] hover:text-[color:var(--nx-danger,var(--nx-text))]">
                        @svg('heroicon-o-eye-slash', 'h-3.5 w-3.5') Ignorieren
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open"
                                class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-text)] transition-colors hover:border-[color:var(--nx-accent)]">
                            @svg('heroicon-o-clock', 'h-3.5 w-3.5') Wiedervorlage
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.100ms
                             class="absolute left-0 z-30 mt-1 w-44 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
                            @foreach(['In 1 Stunde' => 1, 'Heute Abend' => 8, 'Morgen früh' => 20, 'Nächste Woche' => 168] as $label => $h)
                                <button type="button" wire:click="bulkSnooze({{ $h }})" @click="open = false"
                                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                                    @svg('heroicon-o-clock', 'h-3.5 w-3.5 shrink-0 text-[color:var(--nx-faint)]')
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Der Strom: eine durchlaufende, geteilte Liste über die ganze Breite --}}
        <div class="min-h-0 flex-1 overflow-y-auto">
            @php($prevSection = null)
            <div class="divide-y divide-[color:var(--nx-line)]">
                @forelse($items as $it)
                    @if(!empty($it['section']) && $it['section'] !== $prevSection)
                        @php($prevSection = $it['section'])
                        <div class="sticky top-0 z-10 flex items-center gap-2 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-6 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-[color:var(--nx-faint)]">
                            {{ $it['section'] === 'upcoming' ? 'Anstehend' : 'Vergangen' }}
                        </div>
                    @endif
                    @include('home::partials.inbox.card', ['it' => $it, 'selectable' => true, 'selected' => $selected])
                @empty
                    <div class="px-6 py-16">
                        <x-nx-empty icon="heroicon-o-check-circle">Nichts hier — sauber.</x-nx-empty>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @include('home::partials.inbox.undo-toast')
</x-ui-page>
