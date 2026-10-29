<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    <x-ui-page-container width="contained">
        {{-- Zurück in den Strom --}}
        <a href="{{ route('home.inbox') }}" wire:navigate
           class="mb-4 inline-flex items-center gap-1.5 text-sm text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">
            @svg('heroicon-o-arrow-left', 'h-4 w-4') Eingang
        </a>

        @if(empty($item))
            <x-nx-empty icon="heroicon-o-inbox">
                Dieser Eingang existiert nicht mehr.
                <x-slot name="action">
                    <x-nx-button variant="secondary" size="sm" :href="route('home.inbox')" wire:navigate>Zurück zum Eingang</x-nx-button>
                </x-slot>
            </x-nx-empty>
        @else
            <div class="space-y-5">
                {{-- Kopf: Bedeutung zuerst --}}
                <div>
                    <div class="flex items-center gap-2 text-xs text-[color:var(--nx-faint)]">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[color:var(--nx-accent-soft)] px-2 py-0.5 font-medium text-[color:var(--nx-muted)]">
                            @svg($item['icon'] ?? 'heroicon-o-inbox', 'h-3.5 w-3.5') {{ $item['channel_label'] ?? '' }}
                        </span>
                        @if(!empty($item['time']))<span>{{ $item['time'] }}</span>@endif
                    </div>
                    <h1 class="mt-2 text-xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $item['subject'] ?: ($item['sender'] ?? 'Eingang') }}</h1>
                    @if(!empty($item['sender']))
                        <div class="mt-1 text-sm text-[color:var(--nx-muted)]">{{ $item['sender'] }}</div>
                    @endif
                </div>

                {{-- Aktions-Toolbar: links Triage, rechts Kontext-Aktionen --}}
                <div class="flex flex-wrap items-center gap-2 border-y border-[color:var(--nx-line)] py-3">
                    {{-- Triage --}}
                    <button type="button" wire:click="markDone({{ (int) $item['inbox_id'] }})" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-md bg-[color:var(--nx-text)] px-3 py-1.5 text-sm font-medium text-[color:var(--nx-on-accent)] hover:opacity-90">
                        @svg('heroicon-o-check', 'h-4 w-4') Erledigt
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" title="Später – kommt automatisch zurück"
                                class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-3 py-1.5 text-sm font-medium text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]">
                            @svg('heroicon-o-clock', 'h-4 w-4') Später
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.100ms
                             class="absolute left-0 z-20 mt-1 w-44 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
                            <div class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Später erinnern</div>
                            @foreach(['In 1 Stunde' => 1, 'Heute Abend' => 8, 'Morgen früh' => 20, 'Nächste Woche' => 168] as $label => $h)
                                <button type="button" wire:click="snooze({{ (int) $item['inbox_id'] }}, {{ $h }})" @click="open = false"
                                        class="block w-full px-3 py-1.5 text-left text-xs text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <span class="mx-1 h-5 w-px bg-[color:var(--nx-line)]"></span>

                    {{-- Kontext-Aktionen --}}
                    @if(in_array($item['channel'] ?? '', ['mail', 'message'], true))
                        <button type="button" disabled
                                class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-3 py-1.5 text-sm text-[color:var(--nx-faint)]">
                            @svg('heroicon-o-arrow-uturn-left', 'h-4 w-4') Antworten
                            <span class="rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px]">bald</span>
                        </button>
                    @endif
                    <button type="button" wire:click="toTask" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-3 py-1.5 text-sm text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                        @svg('heroicon-o-clipboard-document-check', 'h-4 w-4 text-[color:var(--nx-muted)]') Zur Aufgabe
                    </button>
                    <button type="button" wire:click="toTicket" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-3 py-1.5 text-sm text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                        @svg('heroicon-o-lifebuoy', 'h-4 w-4 text-[color:var(--nx-muted)]') Zum Ticket
                    </button>
                    <button type="button" disabled
                            class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-3 py-1.5 text-sm text-[color:var(--nx-faint)]">
                        @svg('heroicon-o-calendar-days', 'h-4 w-4') An Agenda
                        <span class="rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px]">bald</span>
                    </button>
                </div>

                @if($notice !== '')
                    <div class="flex items-center gap-2 text-xs font-medium text-[color:var(--nx-success)]">
                        @svg('heroicon-o-check-circle', 'h-4 w-4') {{ $notice }}
                    </div>
                @endif

                {{-- „An Knoten hängen" lebt im globalen Kontext-Panel (page-context). --}}
                <div class="flex items-start gap-2 rounded-[8px] bg-[color:var(--nx-accent-soft)] px-3 py-2 text-xs text-[color:var(--nx-muted)]">
                    @svg('heroicon-o-link', 'mt-0.5 h-4 w-4 shrink-0 text-[color:var(--nx-faint)]')
                    <span>An einen Org-Knoten hängen findest du rechts im <strong class="font-medium text-[color:var(--nx-text)]">Kontext-Panel</strong> (Verknüpfungen).</span>
                </div>

                @if(!empty($item['summary']))
                    <x-nx-callout variant="info" title="KI-Zusammenfassung">{{ $item['summary'] }}</x-nx-callout>
                @endif

                {{-- Kanal-spezifischer Body (Anker der Einheit) --}}
                @include('home::partials.inbox.body-dispatch', ['item' => $item, 'inUnit' => false])

                {{-- Facetten der Einheit (z. B. die Aufnahme unter dem Meeting) --}}
                @php($facetMeta = ['recording' => ['heroicon-o-microphone','Aufnahme'], 'mail' => ['heroicon-o-envelope','Mail'], 'call' => ['heroicon-o-phone','Anruf'], 'meeting' => ['heroicon-o-calendar-days','Meeting'], 'message' => ['heroicon-o-chat-bubble-left-right','Nachricht']])
                @foreach($item['facet_details'] ?? [] as $facet)
                    @php([$fIcon, $fLabel] = $facetMeta[$facet['channel'] ?? ''] ?? ['heroicon-o-link','Verknüpft'])
                    <div class="mt-6 border-t border-[color:var(--nx-line)] pt-4">
                        <div class="mb-3 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                            @svg($fIcon, 'h-3.5 w-3.5') {{ $fLabel }}
                        </div>
                        @include('home::partials.inbox.body-dispatch', ['item' => $facet, 'inUnit' => true])
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
