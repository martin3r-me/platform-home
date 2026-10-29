<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- „Heute"-Schiene: orientierende Kontext-Spalte links (aus dem früheren
         Dashboard). Collapsible/resizable über die ui-page-sidebar. --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Heute" icon="heroicon-o-sun" width="w-72" :defaultOpen="true" side="left">
            @include('home::partials.today-rail')
        </x-ui-page-sidebar>
    </x-slot>

    {{-- STROM: ein durchlaufendes Postfach über die ganze Breite (Outlook-artig).
         Oben die Filter, darunter die Zeilen. Jede Zeile trägt nur Triage; ein
         Klick auf die Zeile öffnet die eigene Item-View (/eingang/{item}). --}}
    <div class="flex min-h-0 flex-1 flex-col">
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
                    @include('home::partials.inbox.card', ['it' => $it])
                @empty
                    <div class="px-6 py-16">
                        <x-nx-empty icon="heroicon-o-check-circle">Nichts hier — sauber.</x-nx-empty>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-ui-page>
