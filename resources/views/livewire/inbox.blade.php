<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- STROM (Mitte): ein Fluss aus Kacheln. Oben die Filter, darunter die Items. --}}
    <div class="flex min-h-0 flex-1 flex-col">
        {{-- Filterleiste: Kanal × Status, kompakt & ruhig --}}
        <div class="shrink-0 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
            <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-1 px-4 py-2">
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
            <div class="mx-auto max-w-3xl space-y-3 px-4 py-5">
                @php($prevSection = null)
                @forelse($items as $it)
                    @if(!empty($it['section']) && $it['section'] !== $prevSection)
                        @php($prevSection = $it['section'])
                        <div class="flex items-center gap-2 pt-2 text-[10px] font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] first:pt-0">
                            {{ $it['section'] === 'upcoming' ? 'Anstehend' : 'Vergangen' }}
                            <span class="h-px flex-1 bg-[color:var(--nx-line)]"></span>
                        </div>
                    @endif
                    @php($isSel = $selected && $selected['id'] === $it['id'])
                    @include('home::partials.inbox.card', [
                        'it'         => $isSel ? $selected : $it,
                        'isSelected' => $isSel,
                    ])
                @empty
                    <x-nx-empty icon="heroicon-o-check-circle">Nichts hier — sauber.</x-nx-empty>
                @endforelse
            </div>
        </div>
    </div>

    {{-- KONTEXT-BAR (rechts): was ich mit der gewählten Kachel TUE.
         Beziehungs-/Kontext-Aktionen leben hier, nicht im Strom. „An Knoten hängen"
         (Verknüpfungen) kommt über das globale Kontext-Panel (page-context). --}}
    <aside class="flex w-80 shrink-0 flex-col border-l border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
        @if($selected)
            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 space-y-5">
                <div>
                    <div class="text-[11px] font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Kontext</div>
                    <div class="mt-1 text-sm font-semibold leading-snug text-[color:var(--nx-text)]">{{ $selected['subject'] ?: ($selected['sender'] ?? 'Eingang') }}</div>
                    @if(!empty($selected['sender']) && !empty($selected['subject']))
                        <div class="mt-0.5 text-xs text-[color:var(--nx-muted)]">{{ $selected['sender'] }}</div>
                    @endif
                </div>

                @if($notice !== '')
                    <div class="flex items-center gap-2 rounded-md bg-[color:var(--nx-accent-soft)] px-3 py-2 text-xs font-medium text-[color:var(--nx-text)]">
                        @svg('heroicon-o-check-circle', 'h-4 w-4') {{ $notice }}
                    </div>
                @endif

                @if($selected['real'] ?? false)
                    {{-- Aktion --}}
                    <div class="space-y-2">
                        <div class="text-[11px] font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Aktion</div>

                        @if(in_array($selected['channel'] ?? '', ['mail', 'message'], true))
                            <button type="button" disabled
                                    class="flex w-full cursor-not-allowed items-center gap-2 rounded-md border border-[color:var(--nx-line)] px-3 py-2 text-left text-sm text-[color:var(--nx-faint)]">
                                @svg('heroicon-o-arrow-uturn-left', 'h-4 w-4 shrink-0') Antworten
                                <span class="ml-auto rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px]">bald</span>
                            </button>
                        @endif

                        <button type="button" wire:click="toTask" wire:loading.attr="disabled"
                                class="flex w-full items-center gap-2 rounded-md border border-[color:var(--nx-line)] px-3 py-2 text-left text-sm text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                            @svg('heroicon-o-clipboard-document-check', 'h-4 w-4 shrink-0 text-[color:var(--nx-muted)]') Zur Aufgabe machen
                        </button>

                        <button type="button" wire:click="toTicket" wire:loading.attr="disabled"
                                class="flex w-full items-center gap-2 rounded-md border border-[color:var(--nx-line)] px-3 py-2 text-left text-sm text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                            @svg('heroicon-o-lifebuoy', 'h-4 w-4 shrink-0 text-[color:var(--nx-muted)]') Zum Ticket machen
                        </button>

                        <button type="button" disabled
                                class="flex w-full cursor-not-allowed items-center gap-2 rounded-md border border-[color:var(--nx-line)] px-3 py-2 text-left text-sm text-[color:var(--nx-faint)]">
                            @svg('heroicon-o-calendar-days', 'h-4 w-4 shrink-0') An Agenda hängen
                            <span class="ml-auto rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px]">bald</span>
                        </button>
                    </div>

                    {{-- Verknüpfungen --}}
                    <div class="space-y-2 border-t border-[color:var(--nx-line)] pt-4">
                        <div class="text-[11px] font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Verknüpfungen</div>
                        <div class="flex items-start gap-2 text-xs text-[color:var(--nx-muted)]">
                            @svg('heroicon-o-link', 'mt-0.5 h-4 w-4 shrink-0 text-[color:var(--nx-faint)]')
                            <span>An einen Org-Knoten hängen findest du im <strong class="font-medium text-[color:var(--nx-text)]">Kontext-Panel</strong> rechts.</span>
                        </div>
                    </div>
                @else
                    <div class="rounded-md border border-dashed border-[color:var(--nx-line)] px-3 py-4 text-xs text-[color:var(--nx-faint)]">
                        Platzhalter-Eintrag — Aktionen stehen für echte Eingänge bereit.
                    </div>
                @endif
            </div>
        @else
            <div class="flex flex-1 items-center justify-center px-6 text-center">
                <div class="text-xs text-[color:var(--nx-faint)]">
                    @svg('heroicon-o-cursor-arrow-rays', 'mx-auto mb-2 h-5 w-5')
                    Wähle eine Kachel — hier erscheinen die Aktionen.
                </div>
            </div>
        @endif
    </aside>
</x-ui-page>
