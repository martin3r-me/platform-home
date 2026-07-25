{{-- Teams: Chat-Verlauf (Bubbles) --}}
<div class="space-y-3">
    <div class="text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Verlauf</div>

    <div class="space-y-2">
        @foreach($item['chat'] ?? [] as $m)
            <div class="flex items-end gap-2 {{ ($m['me'] ?? false) ? 'flex-row-reverse' : '' }}">
                <x-nx-avatar :name="$m['from']" size="sm" />
                <div class="rounded-[8px] px-3 py-2 {{ ($m['me'] ?? false) ? 'bg-[color:var(--nx-accent-soft)]' : 'bg-[color:var(--nx-hover)]' }}">
                    <div class="text-[10px] text-[color:var(--nx-faint)]">{{ $m['from'] }} · {{ $m['time'] }}</div>
                    <div class="text-sm text-[color:var(--nx-text)]">{{ $m['body'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
