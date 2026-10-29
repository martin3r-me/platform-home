{{-- Mail: Verlauf als aufgeräumte Lese-Ansicht.
     Jede Nachricht zeigt nur ihren NEUEN Teil; der zitierte Vorgänger-Verlauf
     ($m['quoted'], in Core abgetrennt) liegt eingeklappt dahinter. --}}
<div class="space-y-3">
    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
        <span>Verlauf</span>
        @if(count($item['thread'] ?? []) > 1)
            <span class="tabular-nums text-[color:var(--nx-faint)]">{{ count($item['thread']) }}</span>
        @endif
    </div>

    @if(($item['older'] ?? 0) > 0)
        <button type="button" class="w-full rounded-md border border-[color:var(--nx-line)] px-3 py-2 text-left text-xs text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)]">
            {{ $item['older'] }} frühere Nachrichten anzeigen
        </button>
    @endif

    @foreach($item['thread'] ?? [] as $m)
        <article class="overflow-hidden rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
            <header class="flex items-center justify-between gap-2 border-b border-[color:var(--nx-line)] px-4 py-2.5">
                <div class="flex min-w-0 items-center gap-2.5">
                    <x-nx-avatar :name="$m['from']" size="sm" />
                    <span class="truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $m['from'] }}</span>
                    @if(!empty($m['me']))
                        <span class="shrink-0 rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">Du</span>
                    @endif
                </div>
                <span class="shrink-0 text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $m['time'] }}</span>
            </header>

            <div class="px-4 py-3.5">
                @if(trim($m['body'] ?? '') !== '')
                    <div class="max-w-[68ch] whitespace-pre-line text-[15px] leading-relaxed text-[color:var(--nx-text)] [overflow-wrap:anywhere]">{{ $m['body'] }}</div>
                @else
                    <div class="text-sm italic text-[color:var(--nx-faint)]">Kein Text.</div>
                @endif

                @if(!empty($m['quoted']))
                    <div x-data="{ open: false }" class="mt-3">
                        <button type="button" @click="open = !open"
                                class="inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span x-bind:class="open ? 'rotate-180' : ''" class="inline-flex transition-transform duration-150">
                                @svg('heroicon-o-chevron-down', 'h-3.5 w-3.5')
                            </span>
                            <span x-show="!open">Zitierten Verlauf anzeigen</span>
                            <span x-show="open" x-cloak>Zitierten Verlauf ausblenden</span>
                        </button>
                        <div x-show="open" x-collapse x-cloak
                             class="mt-2 max-w-[68ch] whitespace-pre-line border-l-2 border-[color:var(--nx-line)] pl-3 text-[13px] leading-relaxed text-[color:var(--nx-faint)] [overflow-wrap:anywhere]">{{ $m['quoted'] }}</div>
                    </div>
                @endif
            </div>
        </article>
    @endforeach
</div>
