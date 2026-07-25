{{-- Mail: Thread / Verlauf + Antwort --}}
<div class="space-y-3">
    <div class="text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Verlauf</div>

    @if(($item['older'] ?? 0) > 0)
        <button type="button" class="w-full rounded-md border border-[color:var(--nx-line)] px-3 py-2 text-left text-xs text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)]">
            {{ $item['older'] }} frühere Nachrichten anzeigen
        </button>
    @endif

    @foreach($item['thread'] ?? [] as $m)
        <div class="rounded-[8px] border border-[color:var(--nx-line)] p-3">
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <x-nx-avatar :name="$m['from']" size="sm" />
                    <span class="text-sm font-medium text-[color:var(--nx-text)]">{{ $m['from'] }}</span>
                </div>
                <span class="text-xs text-[color:var(--nx-faint)]">{{ $m['time'] }}</span>
            </div>
            <div class="whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-text)]">{{ $m['body'] }}</div>
        </div>
    @endforeach
</div>
