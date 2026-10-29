<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Zuhause" icon="heroicon-o-home" />
    </x-slot>

    {{-- ZUHAUSE: das warme persönliche Cockpit. Begrüßung + Check-in, dann die
         präsente (aber begrenzte) Eingang-Box neben den Kern-Widgets (Zeit, Aufgaben),
         darunter der Katalog der übrigen Widgets — je ein Person-Kontrakt. Der volle
         Posteingang lebt auf /eingang; hier ist er ein Fenster, kein Fass. --}}
    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-6xl px-6 py-7">

            {{-- Begrüßung + Check-in --}}
            <div class="mb-6 flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">
                        {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                    </h1>
                    <p class="mt-1 text-sm text-[color:var(--nx-muted)]">
                        {{ $dateLabel }}@if($streak > 0) · 🔥 {{ $streak }} {{ $streak === 1 ? 'Tag' : 'Tage' }} Streak @endif
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

            {{-- Reihe 1: Eingang-Box (präsent, begrenzt) + Kern-Widgets --}}
            <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">

                {{-- Eingang: innen scrollbar, nur offene Items --}}
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

                {{-- Kern-Widgets: Zeit + Aufgaben --}}
                <div class="flex flex-col gap-4">
                    @include('home::partials.widgets.stamp')
                    @include('home::partials.widgets.tasks')
                </div>
            </div>

            {{-- Reihe 2: Widget-Katalog (je Kontrakt ein Baustein; später abonnierbar) --}}
            @if(!empty($widgets))
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($widgets as $w)
                        @include('home::partials.widgets.' . $w)
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-ui-page>
