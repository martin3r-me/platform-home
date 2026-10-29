{{-- Eingang-Zeile (Outlook-artig). Oben IMMER klein & ausgeschrieben der Typ
     (E-MAIL / MEETING / AUFNAHME / …), darunter Betreff + 1-Zeilen-Vorschau.
     Modell: die Zeile trägt NUR Triage (Erledigt / Wiedervorlage, echte inbox_id);
     ein Klick auf die Zeile (stretched link) öffnet die Item-View (/eingang/{item}).
     $it: Listen-Item (mapRow). $dense (optional) = engere Variante für die Box. --}}
@php
    $real   = (bool) ($it['real'] ?? false);
    $teaser = trim((string) ($it['summary'] ?? $it['preview'] ?? ''));
    $unread = !empty($it['unread']);
    $padX   = ($dense ?? false) ? 'px-4' : 'px-6';
    $type   = mb_strtoupper(trim((string) ($it['channel_label'] ?? 'Eingang')));

    $badges = collect();
    if (!empty($it['is_series'])) $badges->push(['heroicon-o-arrow-path', 'Serie' . (($it['series_count'] ?? 1) > 1 ? ' · ' . $it['series_count'] : '')]);
    if (!empty($it['is_thread'])) $badges->push(['heroicon-o-chat-bubble-left-right', 'Thread · ' . ($it['thread_count'] ?? 1)]);
    $facetMeta = ['recording' => ['heroicon-o-microphone','Aufnahme'], 'mail' => ['heroicon-o-envelope','Mail'], 'call' => ['heroicon-o-phone','Anruf'], 'meeting' => ['heroicon-o-calendar-days','Meeting'], 'message' => ['heroicon-o-chat-bubble-left-right','Nachricht']];
    foreach (collect($it['facets'] ?? [])->pluck('channel')->unique() as $fch) {
        $badges->push($facetMeta[$fch] ?? ['heroicon-o-link','Verknüpft']);
    }
@endphp

<article class="group relative flex items-start gap-3 {{ $padX }} py-2.5 transition-colors hover:bg-[color:var(--nx-hover)] {{ $real ? '' : 'opacity-70' }}">

    {{-- Unread-Akzent ganz links --}}
    @if($unread)
        <span class="absolute inset-y-0 left-0 w-0.5 bg-[color:var(--nx-accent)]"></span>
    @endif

    {{-- Stretched Link: ganze Zeile klickbar --}}
    @if($real)
        <a href="{{ route('home.inbox', ['item' => $it['inbox_id']]) }}" wire:navigate
           class="absolute inset-0" aria-label="{{ $it['subject'] ?: ($it['sender'] ?? 'Eingang') }} öffnen"></a>
    @endif

    <div class="pointer-events-none relative min-w-0 flex-1">
        {{-- Zeile 1: Typ (klein, ausgeschrieben) · Absender · Zeit --}}
        <div class="flex items-center gap-1.5 text-[11px] text-[color:var(--nx-faint)]">
            @svg($it['icon'] ?? 'heroicon-o-inbox', 'h-3.5 w-3.5 shrink-0 text-[color:var(--nx-muted)]')
            <span class="shrink-0 font-semibold uppercase tracking-wider text-[color:var(--nx-muted)]">{{ $type }}</span>
            @if(!empty($it['sender']))
                <span class="shrink-0">·</span>
                <span class="truncate">{{ $it['sender'] }}</span>
            @endif
            <span class="ml-auto shrink-0 tabular-nums">{{ $it['time'] ?? '' }}</span>
            @if($unread)
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-accent)]"></span>
            @endif
        </div>

        {{-- Zeile 2: Betreff --}}
        <div class="mt-1 truncate text-[14px] {{ $unread ? 'font-semibold' : 'font-medium' }} text-[color:var(--nx-text)]">
            {{ $it['subject'] ?: ($it['sender'] ?? 'Eingang') }}
        </div>

        {{-- Zeile 3: Vorschau --}}
        @if($teaser !== '')
            <div class="mt-0.5 truncate text-[13px] leading-relaxed text-[color:var(--nx-muted)]">{{ $teaser }}</div>
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

    {{-- Triage rechts: klar als Aktionen erkennbar, immer erreichbar, echte inbox_id.
         Erledigt = Hauptaktion (beschriftet, grüner Hover „abgehakt → raus aus dem
         Eingang"); Wiedervorlage = Uhr mit Preset-Menü. --}}
    @if($real)
        <div class="relative z-10 flex shrink-0 items-center gap-1.5">
            <button type="button" wire:click="markDone({{ $it['inbox_id'] }})" wire:loading.attr="disabled"
                    title="Erledigt – raus aus dem Eingang"
                    class="inline-flex h-7 items-center gap-1 rounded-md border border-[color:var(--nx-line)] px-2 text-[11px] font-medium text-[color:var(--nx-muted)] transition-colors hover:border-[color:var(--nx-success)] hover:bg-[color:var(--nx-active)] hover:text-[color:var(--nx-success)]">
                @svg('heroicon-o-check', 'h-3.5 w-3.5 shrink-0')
                <span class="whitespace-nowrap">Erledigt</span>
            </button>

            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" title="Wiedervorlage – später erneut vorlegen"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-[color:var(--nx-line)] text-[color:var(--nx-muted)] transition-colors hover:border-[color:var(--nx-accent)] hover:bg-[color:var(--nx-active)] hover:text-[color:var(--nx-text)]">
                    @svg('heroicon-o-clock', 'h-3.5 w-3.5')
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.100ms
                     class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
                    <div class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Wiedervorlage</div>
                    @foreach(['In 1 Stunde' => 1, 'Heute Abend' => 8, 'Morgen früh' => 20, 'Nächste Woche' => 168] as $label => $h)
                        <button type="button" wire:click="snooze({{ $it['inbox_id'] }}, {{ $h }})" @click="open = false"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                            @svg('heroicon-o-clock', 'h-3.5 w-3.5 shrink-0 text-[color:var(--nx-faint)]')
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</article>
