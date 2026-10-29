{{-- Universelle Eingang-Kachel (Stream).
     Modell: VIEW = typ-dispatched (body-dispatch, aufgeklappt), EVENTS = zwei Klassen:
       · Triage (Erledigt / Wiedervorlage) — generisch, an der Kachel, id-gebunden.
       · Kontext (Antworten / → Aufgabe / an Knoten) — typ-abhängig, in der rechten Bar.
     Bedeutung zuerst: Betreff + nächster Schritt oben, das Rohe klappt darunter auf.
     $it: Listen-Item (bei Auswahl das detail-gemergte $selected). $isSelected: offen? --}}
@php
    $real   = (bool) ($it['real'] ?? false);
    $teaser = trim((string) ($it['summary'] ?? $it['preview'] ?? ''));
    $open   = (bool) ($isSelected ?? false);
@endphp

<article
    class="group rounded-[12px] border bg-[color:var(--nx-surface)] transition-colors
           {{ $open ? 'border-[color:var(--nx-accent)]' : 'border-[color:var(--nx-line)] hover:border-[color:var(--nx-muted)]' }}">

    {{-- Kopf: Bedeutung zuerst. Ganzer Kopf klappt die Kachel auf/zu. --}}
    <button type="button" wire:click="toggleItem({{ $it['id'] }})"
            class="flex w-full items-start gap-3 px-4 pt-3.5 text-left">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">
            @svg($it['icon'] ?? 'heroicon-o-inbox', 'h-4 w-4')
        </span>

        <div class="min-w-0 flex-1">
            {{-- Meta-Zeile (leise) --}}
            <div class="flex items-center gap-2 text-[11px] text-[color:var(--nx-faint)]">
                <span class="font-medium text-[color:var(--nx-muted)]">{{ $it['channel_label'] ?? '' }}</span>
                <span>·</span>
                <span class="truncate">{{ $it['sender'] ?? '' }}</span>
                <span class="ml-auto shrink-0 tabular-nums">{{ $it['time'] ?? '' }}</span>
                @if(!empty($it['unread']))
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-accent)]"></span>
                @endif
            </div>

            {{-- Betreff = die Bedeutung --}}
            <div class="mt-0.5 truncate text-[15px] font-semibold text-[color:var(--nx-text)]">
                {{ $it['subject'] ?: ($it['sender'] ?? 'Eingang') }}
            </div>

            {{-- Nächster Schritt / Teaser (eingeklappt sichtbar) --}}
            @if(!$open && $teaser !== '')
                <div class="mt-0.5 line-clamp-2 text-[13px] leading-relaxed text-[color:var(--nx-muted)]">{{ $teaser }}</div>
            @endif

            {{-- Badges: Thread / Serie / Facetten --}}
            @php($badges = collect())
            @if(!empty($it['is_series']))  @php($badges->push(['heroicon-o-arrow-path', 'Serie' . (($it['series_count'] ?? 1) > 1 ? ' · ' . $it['series_count'] : '')])) @endif
            @if(!empty($it['is_thread']))  @php($badges->push(['heroicon-o-chat-bubble-left-right', 'Thread · ' . ($it['thread_count'] ?? 1)])) @endif
            @if(!empty($it['facets']))
                @php($facetMeta = ['recording' => ['heroicon-o-microphone','Aufnahme'], 'mail' => ['heroicon-o-envelope','Mail'], 'call' => ['heroicon-o-phone','Anruf'], 'meeting' => ['heroicon-o-calendar-days','Meeting'], 'message' => ['heroicon-o-chat-bubble-left-right','Nachricht']])
                @foreach(collect($it['facets'])->pluck('channel')->unique() as $fch)
                    @php([$fi, $fl] = $facetMeta[$fch] ?? ['heroicon-o-link','Verknüpft'])
                    @php($badges->push([$fi, $fl]))
                @endforeach
            @endif
            @if($badges->isNotEmpty())
                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                    @foreach($badges as [$bi, $bl])
                        <span class="inline-flex items-center gap-1 rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">
                            @svg($bi, 'h-3 w-3'){{ $bl }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Chevron (auf/zu) --}}
        <span class="mt-1 shrink-0 text-[color:var(--nx-faint)] transition-transform {{ $open ? 'rotate-180' : '' }}">
            @svg('heroicon-o-chevron-down', 'h-4 w-4')
        </span>
    </button>

    {{-- Triage-Leiste: generisch, an der Kachel. Nur echte Items triagierbar. --}}
    @if($real)
        <div class="flex items-center gap-1.5 px-4 pb-3 pt-2.5 {{ $open ? '' : 'opacity-0 group-hover:opacity-100 transition-opacity' }}">
            <button type="button" wire:click="markDone({{ $it['id'] }})" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-md bg-[color:var(--nx-text)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-on-accent)] hover:opacity-90">
                @svg('heroicon-o-check', 'h-3.5 w-3.5') Erledigt
            </button>

            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]">
                    @svg('heroicon-o-clock', 'h-3.5 w-3.5') Wiedervorlage
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     x-transition.opacity.duration.100ms
                     class="absolute left-0 z-20 mt-1 w-44 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
                    @foreach(['In 1 Stunde' => 1, 'Heute Abend' => 8, 'Morgen früh' => 20, 'Nächste Woche' => 168] as $label => $h)
                        <button type="button" wire:click="snooze({{ $h }}, {{ $it['id'] }})" @click="open = false"
                                class="block w-full px-3 py-1.5 text-left text-xs text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Aufgeklappt: der typ-spezifische Inhalt (VIEW) --}}
    @if($open)
        <div class="border-t border-[color:var(--nx-line)] px-4 py-4">
            @if(!empty($it['summary']))
                <x-nx-callout variant="info" title="KI-Zusammenfassung">{{ $it['summary'] }}</x-nx-callout>
                <div class="h-4"></div>
            @endif

            @include('home::partials.inbox.body-dispatch', ['item' => $it, 'inUnit' => false])

            {{-- Facetten der Einheit (z. B. die Aufnahme unter dem Meeting) --}}
            @php($facetMeta2 = ['recording' => ['heroicon-o-microphone','Aufnahme'], 'mail' => ['heroicon-o-envelope','Mail'], 'call' => ['heroicon-o-phone','Anruf'], 'meeting' => ['heroicon-o-calendar-days','Meeting'], 'message' => ['heroicon-o-chat-bubble-left-right','Nachricht']])
            @foreach($it['facet_details'] ?? [] as $facet)
                @php([$fIcon, $fLabel] = $facetMeta2[$facet['channel'] ?? ''] ?? ['heroicon-o-link','Verknüpft'])
                <div class="mt-6 border-t border-[color:var(--nx-line)] pt-4">
                    <div class="mb-3 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                        @svg($fIcon, 'h-3.5 w-3.5') {{ $fLabel }}
                    </div>
                    @include('home::partials.inbox.body-dispatch', ['item' => $facet, 'inUnit' => true])
                </div>
            @endforeach
        </div>
    @endif
</article>
