{{-- Agenda-Reiter im Kontext-Panel: Datensatz auf Agenden legen / herunternehmen --}}
<div class="flex flex-col h-full text-[var(--t-text)]">
    <div class="px-3 py-2 border-b border-white/10 text-[11px] text-[var(--t-text-muted)] flex items-center gap-2">
        <span class="flex-1 min-w-0 truncate">
            Auf Agenda legen:
            <span class="text-[var(--t-text)] font-medium">{{ $contextSubject ?? ('#'.$contextId) }}</span>
        </span>
        @if($indexUrl)
            <a href="{{ $indexUrl }}" wire:navigate class="shrink-0 hover:text-[var(--t-text)] transition">Alle Agenden</a>
        @endif
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto p-3 space-y-3">
        @if(count($this->agendas))
            <ul class="space-y-1">
                @foreach($this->agendas as $agenda)
                    <li wire:key="cp-agenda-{{ $agenda['id'] }}"
                        class="group flex items-center gap-2 px-2 py-1.5 rounded text-[12px] transition {{ $agenda['on'] ? 'bg-[var(--t-accent)]/10' : 'bg-white/5 hover:bg-white/10' }}">
                        <button type="button" wire:click="toggle({{ $agenda['id'] }})" wire:loading.attr="disabled"
                                class="flex-1 min-w-0 flex items-center gap-2 text-left"
                                title="{{ $agenda['on'] ? 'Von der Agenda nehmen' : 'Auf die Agenda legen' }}">
                            <span class="w-4 h-4 shrink-0 rounded border flex items-center justify-center transition
                                {{ $agenda['on'] ? 'bg-[var(--t-accent)] border-[var(--t-accent)] text-white' : 'border-[var(--t-border)] group-hover:border-[var(--t-accent)]' }}">
                                @if($agenda['on'])
                                    @svg('heroicon-s-check', 'w-3 h-3')
                                @endif
                            </span>
                            <span class="truncate {{ $agenda['on'] ? 'font-medium' : '' }}">{{ $agenda['name'] }}</span>
                            <span class="ml-auto shrink-0 text-[10px] text-[var(--t-text-muted)] tabular-nums">{{ $agenda['count'] ?: '' }}</span>
                        </button>
                        @if($agenda['url'])
                            <a href="{{ $agenda['url'] }}" wire:navigate
                               class="shrink-0 p-0.5 rounded text-[var(--t-text-muted)] opacity-0 group-hover:opacity-100 hover:text-[var(--t-text)] transition"
                               title="Board öffnen">
                                @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-[11px] text-[var(--t-text-muted)]">
                Du hast noch keine Agenda. Leg eine an — der Datensatz landet direkt darauf.
            </p>
        @endif

        <form wire:submit="createAndAttach" class="flex items-center gap-1.5">
            <input type="text" wire:model="newAgendaName" maxlength="120"
                   placeholder="Neue Agenda …"
                   class="flex-1 min-w-0 px-2 py-1.5 text-[12px] rounded border border-[var(--t-border)]/60 bg-transparent text-[var(--t-text)] placeholder:text-[var(--t-text-muted)]/50 focus:outline-none focus:ring-1 focus:ring-[var(--t-accent)]">
            <button type="submit" class="shrink-0 inline-flex items-center gap-1 px-2 py-1.5 rounded text-[11px] font-medium bg-white/5 hover:bg-white/10 transition">
                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                Anlegen
            </button>
        </form>
    </div>
</div>
