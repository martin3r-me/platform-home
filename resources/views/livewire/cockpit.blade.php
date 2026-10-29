<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Zuhause" icon="heroicon-o-home" />
    </x-slot>

    {{-- ZUHAUSE: das warme persönliche Cockpit über die GANZE Breite. Begrüßung +
         Check-in, dann ein Masonry-artiges Raster (1/2/3/4 Spalten je nach Display,
         grid-auto-flow:dense packt die Karten). Die Eingang-Box ist ein breiteres
         Tile (col-span-2), präsent aber begrenzt (nur offene Items, innen scrollbar);
         der volle Posteingang lebt auf /eingang. Jedes Widget = ein Person-Kontrakt. --}}
    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="w-full px-4 py-7 sm:px-6">

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

            {{-- Masonry-Raster: volle Breite, 1/2/3/4 Spalten je nach Display. Echtes
                 Packen („Kacheln rutschen ineinander") via Grid-Row-Span-Technik im
                 Skript unten; ohne JS fällt es auf das normale Raster zurück. --}}
            <div data-masonry class="grid grid-cols-1 items-start gap-4 [grid-auto-flow:dense] sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">

                {{-- Eingang: breiteres Tile (2 Spalten), innen scrollbar, nur offene Items --}}
                <section class="flex h-[500px] flex-col overflow-hidden rounded-[14px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] shadow-sm sm:col-span-2">
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
                    {{-- Klare Typ-Filter: nur vorhandene Kanäle + „Alle", mit Zähler --}}
                    @if(count($boxChannels) > 2)
                        <div class="shrink-0 overflow-x-auto border-b border-[color:var(--nx-line)] px-3 py-1.5">
                            <div class="flex items-center gap-1 whitespace-nowrap">
                                @foreach($boxChannels as $c)
                                    <button type="button" wire:click="setChannel('{{ $c['key'] }}')"
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium transition-colors {{ $channel === $c['key'] ? 'bg-[color:var(--nx-active)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">
                                        @svg($c['icon'], 'h-3 w-3 shrink-0')
                                        {{ $c['label'] }}
                                        <span class="tabular-nums text-[color:var(--nx-faint)]">{{ $c['count'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div class="divide-y divide-[color:var(--nx-line)]">
                            @forelse($items as $it)
                                @include('home::partials.inbox.card', ['it' => $it, 'dense' => true])
                            @empty
                                <div class="flex h-full items-center justify-center px-6 py-16 text-center">
                                    <x-nx-empty icon="heroicon-o-check-circle">{{ $channel === 'all' ? 'Sauber — nichts Offenes im Eingang.' : 'Nichts in diesem Filter.' }}</x-nx-empty>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                {{-- Kern-Widgets --}}
                @include('home::partials.widgets.stamp')
                @include('home::partials.widgets.tasks')

                {{-- Katalog der übrigen Widgets (je Kontrakt ein Baustein; später abonnierbar) --}}
                @foreach($widgets as $w)
                    @include('home::partials.widgets.' . $w)
                @endforeach

            </div>
        </div>
    </div>

    {{-- Masonry-Packer: feine Zeilen (ROW) + pro Kachel berechneter grid-row-end-Span,
         sodass die Karten lückenlos ineinander rutschen — auch das breite Eingang-Tile.
         items-start verhindert Stretch (kein ResizeObserver-Loop). Fehlerresistent:
         bei jedem Fehler zurück aufs normale Raster. --}}
    @script
    <script>
        (() => {
            const ROW = 8, GAP = 16;
            const grid = document.querySelector('[data-masonry]');
            if (!grid) return;

            const items = () => Array.from(grid.children);

            function layout() {
                try {
                    // zurücksetzen, natürliche Höhen messen
                    grid.style.gridAutoRows = '';
                    grid.style.rowGap = '';
                    const cells = items();
                    cells.forEach((i) => { i.style.gridRowEnd = ''; });
                    const heights = cells.map((i) => i.getBoundingClientRect().height);
                    // feine Zeilen + Spans anwenden
                    grid.style.gridAutoRows = ROW + 'px';
                    grid.style.rowGap = '0px';
                    cells.forEach((i, idx) => {
                        const span = Math.max(1, Math.ceil((heights[idx] + GAP) / ROW));
                        i.style.gridRowEnd = 'span ' + span;
                    });
                } catch (e) {
                    grid.style.gridAutoRows = '';
                    grid.style.rowGap = '';
                }
            }

            let raf;
            const schedule = () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(layout); };

            schedule();
            window.addEventListener('load', schedule);
            window.addEventListener('resize', schedule);

            if (window.ResizeObserver) {
                const ro = new ResizeObserver(schedule);
                const observe = () => items().forEach((i) => ro.observe(i));
                observe();
                // Nach Livewire-Morphs (Triage/To-do) neu beobachten + neu packen.
                new MutationObserver(() => { observe(); schedule(); })
                    .observe(grid, { childList: true, subtree: true, characterData: true });
            }
        })();
    </script>
    @endscript
</x-ui-page>
