{{-- Eingang-Zeile im Strom (Outlook-artig, volle Breite).
     Modell: die Zeile trägt NUR Triage (Erledigt / Wiedervorlage, generisch auf
     der echten inbox_id). Ein Klick auf die Zeile navigiert in die eigene
     Item-View (/eingang/{item}) — dort lebt Inhalt + Kontext-Aktionen.
     Technik: die ganze Zeile ist per "stretched link" klickbar (absolut
     liegender <a>); der Inhalt liegt darüber mit pointer-events-none, nur die
     Triage-Buttons fangen ihre Klicks selbst ab (pointer-events-auto, z-10).
     $it: Listen-Item (mapRow). --}}
@php
    $real   = (bool) ($it['real'] ?? false);
    $teaser = trim((string) ($it['summary'] ?? $it['preview'] ?? ''));
    $unread = !empty($it['unread']);

    $badges = collect();
    if (!empty($it['is_series'])) $badges->push(['heroicon-o-arrow-path', 'Serie' . (($it['series_count'] ?? 1) > 1 ? ' · ' . $it['series_count'] : '')]);
    if (!empty($it['is_thread'])) $badges->push(['heroicon-o-chat-bubble-left-right', 'Thread · ' . ($it['thread_count'] ?? 1)]);
    $facetMeta = ['recording' => ['heroicon-o-microphone','Aufnahme'], 'mail' => ['heroicon-o-envelope','Mail'], 'call' => ['heroicon-o-phone','Anruf'], 'meeting' => ['heroicon-o-calendar-days','Meeting'], 'message' => ['heroicon-o-chat-bubble-left-right','Nachricht']];
    foreach (collect($it['facets'] ?? [])->pluck('channel')->unique() as $fch) {
        $badges->push($facetMeta[$fch] ?? ['heroicon-o-link','Verknüpft']);
    }
@endphp

<article class="group relative flex items-center gap-3 px-6 py-3 transition-colors hover:bg-[color:var(--nx-hover)] {{ $real ? '' : 'opacity-70' }}">

    {{-- Unread-Akzent ganz links --}}
    @if($unread)
        <span class="absolute inset-y-0 left-0 w-0.5 bg-[color:var(--nx-accent)]"></span>
    @endif

    {{-- Stretched Link: macht die ganze Zeile klickbar (echte Items) --}}
    @if($real)
        <a href="{{ route('home.inbox', ['item' => $it['inbox_id']]) }}" wire:navigate
           class="absolute inset-0" aria-label="{{ $it['subject'] ?: ($it['sender'] ?? 'Eingang') }} öffnen"></a>
    @endif

    {{-- Icon --}}
    <span class="pointer-events-none relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">
        @svg($it['icon'] ?? 'heroicon-o-inbox', 'h-4 w-4')
    </span>

    {{-- Mitte: Meta-Zeile, Betreff, Vorschau — liegt über dem Link, klickt durch --}}
    <div class="pointer-events-none relative min-w-0 flex-1">
        <div class="flex items-center gap-2 text-[11px] text-[color:var(--nx-faint)]">
            <span class="font-medium text-[color:var(--nx-muted)]">{{ $it['channel_label'] ?? '' }}</span>
            @if(!empty($it['sender']))
                <span>·</span>
                <span class="truncate">{{ $it['sender'] }}</span>
            @endif
            <span class="ml-auto shrink-0 tabular-nums">{{ $it['time'] ?? '' }}</span>
        </div>

        <div class="mt-0.5 truncate text-[14px] {{ $unread ? 'font-semibold' : 'font-medium' }} text-[color:var(--nx-text)]">
            {{ $it['subject'] ?: ($it['sender'] ?? 'Eingang') }}
        </div>

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

    {{-- Triage rechts: generisch, immer erreichbar (nicht hover-versteckt), echte inbox_id --}}
    @if($real)
        <div class="relative z-10 flex shrink-0 items-center gap-1">
            <button type="button" wire:click="markDone({{ $it['inbox_id'] }})" wire:loading.attr="disabled" title="Erledigt"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-active)] hover:text-[color:var(--nx-success,var(--nx-text))]">
                @svg('heroicon-o-check', 'h-4 w-4')
            </button>

            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" title="Wiedervorlage"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-active)] hover:text-[color:var(--nx-text)]">
                    @svg('heroicon-o-clock', 'h-4 w-4')
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.100ms
                     class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
                    @foreach(['In 1 Stunde' => 1, 'Heute Abend' => 8, 'Morgen früh' => 20, 'Nächste Woche' => 168] as $label => $h)
                        <button type="button" wire:click="snooze({{ $it['inbox_id'] }}, {{ $h }})" @click="open = false"
                                class="block w-full px-3 py-1.5 text-left text-xs text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</article>
