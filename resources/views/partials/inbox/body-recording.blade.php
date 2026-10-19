{{-- Aufnahme: Termin-Zuordnung + KI-Zusammenfassung + Transcript --}}
<div class="space-y-4">
    {{-- Zugeordneter Termin --}}
    @if(!empty($item['linked_meeting']))
        <div class="flex items-center gap-2 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-accent-soft)] px-3 py-2">
            @svg('heroicon-o-link', 'w-4 h-4 shrink-0 text-[color:var(--nx-accent)]')
            <span class="text-xs text-[color:var(--nx-muted)]">Gehört zu Termin:</span>
            <span class="min-w-0 truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $item['linked_meeting']['subject'] }}</span>
            @if(!empty($item['linked_meeting']['time']))
                <span class="shrink-0 text-xs text-[color:var(--nx-faint)]">· {{ $item['linked_meeting']['time'] }}</span>
            @endif
            @if($item['linked_meeting']['auto'] ?? false)
                <span class="ml-auto shrink-0 rounded-full bg-[color:var(--nx-accent)] px-2 py-0.5 text-[10px] font-medium text-[color:var(--nx-on-accent)]">auto</span>
            @endif
        </div>
    @endif

    {{-- Player (Mock) --}}
    <div class="flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] p-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)]">
            @svg('heroicon-o-play', 'w-4 h-4')
        </span>
        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[color:var(--nx-accent-soft)]">
            <div class="h-full w-1/3 rounded-full bg-[color:var(--nx-accent)]"></div>
        </div>
        <span class="shrink-0 text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $item['duration'] ?? '' }}</span>
    </div>

    {{-- KI-Zusammenfassung --}}
    @if(($item['enrichment_status'] ?? null) === 'running')
        <div class="flex items-center gap-2 rounded-[8px] border border-dashed border-[color:var(--nx-line)] px-3 py-2 text-xs text-[color:var(--nx-faint)]">
            @svg('heroicon-o-sparkles', 'w-4 h-4 animate-pulse')
            Zusammenfassung wird erstellt …
        </div>
    @elseif(!empty($item['summary']) || !empty($item['tldr']) || !empty($item['headline']))
        <div class="rounded-[8px] border border-[color:var(--nx-line)] p-3 space-y-2">
            <div class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                @svg('heroicon-o-sparkles', 'w-3.5 h-3.5') Zusammenfassung
            </div>
            @if(!empty($item['headline']))
                <div class="text-sm font-semibold text-[color:var(--nx-text)]">{{ $item['headline'] }}</div>
            @endif
            @if(!empty($item['tldr']))
                <div class="text-sm text-[color:var(--nx-muted)]">{{ $item['tldr'] }}</div>
            @endif
            @if(!empty($item['summary']))
                <p class="m-0 whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-text)]">{{ $item['summary'] }}</p>
            @endif
        </div>
    @endif

    {{-- Agenda / Entscheidungen --}}
    @foreach(['agenda' => 'Agenda', 'decisions' => 'Entscheidungen'] as $key => $label)
        @if(!empty($item[$key]))
            <div>
                <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">{{ $label }}</div>
                <ul class="space-y-1">
                    @foreach($item[$key] as $point)
                        <li class="flex gap-2 text-sm text-[color:var(--nx-text)]">
                            <span class="text-[color:var(--nx-faint)]">•</span>
                            <span>{{ is_array($point) ? ($point['text'] ?? '') : $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach

    {{-- Action-Items --}}
    @if(!empty($item['action_items']))
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Action-Items</div>
            <div class="space-y-1.5">
                @foreach($item['action_items'] as $a)
                    @php($text = is_array($a) ? ($a['text'] ?? '') : $a)
                    @continue(empty($text))
                    <div class="flex items-start gap-2 rounded-[8px] border border-[color:var(--nx-line)] px-3 py-2">
                        @svg('heroicon-o-check-circle', 'mt-0.5 w-4 h-4 shrink-0 text-[color:var(--nx-accent)]')
                        <div class="min-w-0 text-sm text-[color:var(--nx-text)]">
                            {{ $text }}
                            @if(is_array($a) && (!empty($a['suggested_owner']) || !empty($a['due_hint'])))
                                <span class="text-xs text-[color:var(--nx-faint)]">
                                    @if(!empty($a['suggested_owner'])) · {{ $a['suggested_owner'] }} @endif
                                    @if(!empty($a['due_hint'])) · {{ $a['due_hint'] }} @endif
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Transcript --}}
    @if(!empty($item['segments']))
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Transcript</div>
            <div class="space-y-2">
                @foreach($item['segments'] as $s)
                    <div class="flex gap-3">
                        <span class="shrink-0 text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $s[0] }}</span>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-[color:var(--nx-muted)]">{{ $s[1] }}:</span>
                            <span class="text-sm text-[color:var(--nx-text)]">{{ $s[2] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
