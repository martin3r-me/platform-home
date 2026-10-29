{{-- Eingang-Kachel im Stream.
     Modell: die Kachel trägt NUR Triage (Erledigt / Wiedervorlage, generisch auf
     der echten inbox_id). Ein Klick auf den Körper navigiert in die eigene
     Item-View (/eingang/{item}) — dort lebt Inhalt + Kontext-Aktionen.
     $it: Listen-Item (mapRow). --}}
@php
    $real   = (bool) ($it['real'] ?? false);
    $teaser = trim((string) ($it['summary'] ?? $it['preview'] ?? ''));

    $badges = collect();
    if (!empty($it['is_series'])) $badges->push(['heroicon-o-arrow-path', 'Serie' . (($it['series_count'] ?? 1) > 1 ? ' · ' . $it['series_count'] : '')]);
    if (!empty($it['is_thread'])) $badges->push(['heroicon-o-chat-bubble-left-right', 'Thread · ' . ($it['thread_count'] ?? 1)]);
    $facetMeta = ['recording' => ['heroicon-o-microphone','Aufnahme'], 'mail' => ['heroicon-o-envelope','Mail'], 'call' => ['heroicon-o-phone','Anruf'], 'meeting' => ['heroicon-o-calendar-days','Meeting'], 'message' => ['heroicon-o-chat-bubble-left-right','Nachricht']];
    foreach (collect($it['facets'] ?? [])->pluck('channel')->unique() as $fch) {
        $badges->push($facetMeta[$fch] ?? ['heroicon-o-link','Verknüpft']);
    }
@endphp

<article class="group overflow-hidden rounded-[12px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] transition-colors hover:border-[color:var(--nx-muted)]">

    @php($headerClass = 'flex w-full items-start gap-3 px-4 pt-3.5 pb-3 text-left')

    {{-- Körper = Link in die Item-View (echte Items). Dummy: nicht klickbar. --}}
    @if($real)
        <a href="{{ route('home.inbox', ['item' => $it['inbox_id']]) }}" wire:navigate class="{{ $headerClass }}">
    @else
        <div class="{{ $headerClass }} opacity-70">
    @endif

        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">
            @svg($it['icon'] ?? 'heroicon-o-inbox', 'h-4 w-4')
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 text-[11px] text-[color:var(--nx-faint)]">
                <span class="font-medium text-[color:var(--nx-muted)]">{{ $it['channel_label'] ?? '' }}</span>
                <span>·</span>
                <span class="truncate">{{ $it['sender'] ?? '' }}</span>
                <span class="ml-auto shrink-0 tabular-nums">{{ $it['time'] ?? '' }}</span>
                @if(!empty($it['unread']))
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-accent)]"></span>
                @endif
            </div>

            <div class="mt-0.5 truncate text-[15px] font-semibold text-[color:var(--nx-text)]">
                {{ $it['subject'] ?: ($it['sender'] ?? 'Eingang') }}
            </div>

            @if($teaser !== '')
                <div class="mt-0.5 line-clamp-2 text-[13px] leading-relaxed text-[color:var(--nx-muted)]">{{ $teaser }}</div>
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

        <span class="mt-1 shrink-0 text-[color:var(--nx-faint)] transition-colors group-hover:text-[color:var(--nx-muted)]">
            @svg('heroicon-o-chevron-right', 'h-4 w-4')
        </span>

    @if($real)
        </a>
    @else
        </div>
    @endif

    {{-- Triage-Leiste: generisch, immer sichtbar, echte inbox_id. --}}
    @if($real)
        <div class="flex items-center gap-1.5 border-t border-[color:var(--nx-line)] bg-[color:var(--nx-bg-soft,transparent)] px-4 py-2">
            <button type="button" wire:click="markDone({{ $it['inbox_id'] }})" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                @svg('heroicon-o-check', 'h-3.5 w-3.5') Erledigt
            </button>

            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-md border border-[color:var(--nx-line)] px-2.5 py-1 text-xs font-medium text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                    @svg('heroicon-o-clock', 'h-3.5 w-3.5') Wiedervorlage
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.100ms
                     class="absolute left-0 z-20 mt-1 w-44 overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] py-1 shadow-sm">
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
