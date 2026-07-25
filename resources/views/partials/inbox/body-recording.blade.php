{{-- Aufnahme: Player + Transcript-Segmente + extrahierte Aktionen --}}
<div class="space-y-4">
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

    {{-- Transcript --}}
    <div>
        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Transcript</div>
        <div class="space-y-2">
            @foreach($item['segments'] ?? [] as $s)
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

    @if(!empty($item['actions']))
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Extrahierte Aktionen</div>
            <div class="flex flex-wrap gap-2">
                @foreach($item['actions'] as $a)
                    <x-nx-button variant="secondary" size="sm">{{ $a }}</x-nx-button>
                @endforeach
            </div>
        </div>
    @endif
</div>
