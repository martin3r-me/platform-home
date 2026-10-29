<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- Liste: Kanal + Status filtern, darunter die Items — Teil des Inhalts, keine innere Sidebar --}}
    <div class="w-80 shrink-0 flex flex-col min-h-0 border-r border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
        <div class="flex flex-wrap gap-1 border-b border-[color:var(--nx-line)] px-3 py-2">
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
        </div>
        {{-- Status-Achse (kombiniert mit dem Kanal) --}}
        <div class="flex flex-wrap gap-1 border-b border-[color:var(--nx-line)] px-3 py-2">
            @foreach($statuses as $s)
                <button type="button" wire:click="setStatus('{{ $s['key'] }}')"
                        class="rounded-md px-2 py-1 text-xs font-medium transition-colors {{ $status === $s['key'] ? 'bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">
                    {{ $s['label'] }}
                </button>
            @endforeach
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto">
            @if(empty($items))
                <x-nx-empty icon="heroicon-o-check-circle">Nichts hier — sauber.</x-nx-empty>
            @else
                <ul class="divide-y divide-[color:var(--nx-line)]">
                    @php $prevSection = null; @endphp
                    @foreach($items as $it)
                        @if(!empty($it['section']) && $it['section'] !== $prevSection)
                            @php $prevSection = $it['section']; @endphp
                            <li class="bg-[color:var(--nx-bg-soft,transparent)] px-4 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-wide text-[color:var(--nx-faint)]">
                                {{ $it['section'] === 'upcoming' ? 'Anstehend' : 'Vergangen' }}
                            </li>
                        @endif
                        <li>
                            <button type="button" wire:click="selectItem({{ $it['id'] }})"
                                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors {{ $selected && $selected['id'] === $it['id'] ? 'bg-[color:var(--nx-active)]' : 'hover:bg-[color:var(--nx-hover)]' }}">
                                @svg($it['icon'], 'mt-0.5 h-4 w-4 shrink-0 text-[color:var(--nx-muted)]')
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm text-[color:var(--nx-text)] {{ $it['unread'] ? 'font-semibold' : '' }}">{{ $it['sender'] }}</span>
                                        <span class="shrink-0 text-[10px] tabular-nums text-[color:var(--nx-faint)]">{{ $it['time'] }}</span>
                                    </div>
                                    <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $it['subject'] }}</div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        @if(!empty($it['is_series']))
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">
                                                @svg('heroicon-o-arrow-path', 'h-3 w-3')
                                                Serie{{ ($it['series_count'] ?? 1) > 1 ? ' · ' . $it['series_count'] : '' }}
                                            </span>
                                        @elseif(!empty($it['is_thread']))
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">
                                                @svg('heroicon-o-chat-bubble-left-right', 'h-3 w-3')
                                                Thread · {{ $it['thread_count'] }}
                                            </span>
                                        @endif
                                        @if(!empty($it['facets']))
                                            @php($facetListMeta = ['recording' => ['Aufnahme', 'heroicon-o-microphone'], 'mail' => ['Mail', 'heroicon-o-envelope'], 'call' => ['Anruf', 'heroicon-o-phone'], 'meeting' => ['Meeting', 'heroicon-o-calendar-days'], 'message' => ['Nachricht', 'heroicon-o-chat-bubble-left-right']])
                                            @foreach(collect($it['facets'])->pluck('channel')->unique() as $fch)
                                                @php([$fLabel, $fIcon] = $facetListMeta[$fch] ?? ['Verknüpft', 'heroicon-o-link'])
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">
                                                    @svg($fIcon, 'h-3 w-3')
                                                    {{ $fLabel }}
                                                </span>
                                            @endforeach
                                        @endif
                                        <span class="truncate text-xs text-[color:var(--nx-faint)]">{{ $it['preview'] }}</span>
                                    </div>
                                </div>
                                @if($it['unread'])
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[color:var(--nx-accent)]"></span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Lesebereich --}}
    <x-ui-page-container width="contained">
        @if($selected)
            <div class="space-y-5">
                <div>
                    <div class="flex items-center gap-2 text-xs text-[color:var(--nx-faint)]">
                        <x-nx-badge variant="neutral">{{ $selected['channel_label'] }}</x-nx-badge>
                        <span>{{ $selected['time'] }}</span>
                    </div>
                    <h1 class="mt-2 text-xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $selected['subject'] }}</h1>
                    <div class="mt-1 text-sm text-[color:var(--nx-muted)]">{{ $selected['sender'] }}</div>
                </div>

                {{-- Action-Bar: triagieren & daraus Arbeit machen (kein Antworten) --}}
                <div class="flex flex-wrap gap-2">
                    <x-nx-button variant="primary" size="sm" wire:click="markDone" wire:loading.attr="disabled">Erledigt</x-nx-button>
                    <x-nx-button variant="secondary" size="sm" wire:click="snooze(4)" wire:loading.attr="disabled">Snooze 4h</x-nx-button>
                    <x-nx-button variant="secondary" size="sm" wire:click="toTask" wire:loading.attr="disabled">→ Aufgabe</x-nx-button>
                    <x-nx-button variant="secondary" size="sm" wire:click="toTicket" wire:loading.attr="disabled">→ Ticket</x-nx-button>
                </div>

                @if($notice !== '')
                    <div class="flex items-center gap-2 text-xs font-medium text-[color:var(--nx-success)]">
                        @svg('heroicon-o-check-circle', 'w-4 h-4')
                        {{ $notice }}
                    </div>
                @endif

                @if(!empty($selected['summary']))
                    <x-nx-callout variant="info" title="KI-Zusammenfassung">
                        {{ $selected['summary'] }}
                    </x-nx-callout>
                @endif

                {{-- Org-Kontext (Verknüpfungen) lebt im rechten Kontext-Panel
                     (core.context-panel). Das gewählte Item wird über HasPageContext
                     als `page-context` gepinnt → App „Verknüpfungen" (organization
                     DimensionLinks) hängt es an einen Org-Knoten. --}}

                {{-- Kanal-spezifischer Body (Anker der Einheit) --}}
                @include('home::partials.inbox.body-dispatch', ['item' => $selected, 'inUnit' => false])

                {{-- Facetten der Einheit (z.B. die Aufnahme unter dem Meeting) --}}
                @php($facetMeta = ['recording' => ['Aufnahme', 'heroicon-o-microphone'], 'mail' => ['Mail', 'heroicon-o-envelope'], 'call' => ['Anruf', 'heroicon-o-phone'], 'meeting' => ['Meeting', 'heroicon-o-calendar-days'], 'message' => ['Nachricht', 'heroicon-o-chat-bubble-left-right']])
                @foreach($selected['facet_details'] ?? [] as $facet)
                    @php([$facetLabel, $facetIcon] = $facetMeta[$facet['channel'] ?? ''] ?? ['Verknüpft', 'heroicon-o-link'])
                    <div class="mt-6 border-t border-[color:var(--nx-line)] pt-4">
                        <div class="mb-3 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                            @svg($facetIcon, 'w-3.5 h-3.5') {{ $facetLabel }}
                        </div>
                        @include('home::partials.inbox.body-dispatch', ['item' => $facet, 'inUnit' => true])
                    </div>
                @endforeach
            </div>
        @else
            <x-nx-empty icon="heroicon-o-inbox">
                Wähle rechts einen Eingang — hier erscheint der Inhalt.
            </x-nx-empty>
        @endif
    </x-ui-page-container>
</x-ui-page>
