<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- STROM: ein Fluss aus Kacheln. Oben die Filter, darunter die Items.
         Die Kachel trägt nur Triage; ein Klick öffnet die eigene Item-View. --}}
    <div class="flex min-h-0 flex-1 flex-col">
        {{-- Filterleiste: Kanal × Status, kompakt & ruhig --}}
        <div class="shrink-0 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
            <div class="mx-auto flex max-w-2xl flex-wrap items-center gap-1 px-4 py-2">
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

        {{-- Der Strom --}}
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto max-w-2xl space-y-3 px-4 py-5">
                @php($prevSection = null)
                @forelse($items as $it)
                    @if(!empty($it['section']) && $it['section'] !== $prevSection)
                        @php($prevSection = $it['section'])
                        <div class="flex items-center gap-2 pt-2 text-[10px] font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] first:pt-0">
                            {{ $it['section'] === 'upcoming' ? 'Anstehend' : 'Vergangen' }}
                            <span class="h-px flex-1 bg-[color:var(--nx-line)]"></span>
                        </div>
                    @endif
                    @include('home::partials.inbox.card', ['it' => $it])
                @empty
                    <x-nx-empty icon="heroicon-o-check-circle">Nichts hier — sauber.</x-nx-empty>
                @endforelse
            </div>
        </div>
    </div>
</x-ui-page>
