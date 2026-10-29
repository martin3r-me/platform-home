{{-- Transcript-Segmente [zeit, sprecher, text] — EINHEITLICH für Meeting/Call/
     Aufnahme (vorher 3× dupliziert). $segments = Liste, $label = Überschrift. --}}
@php($segments = $segments ?? [])
@php($label = $label ?? 'Transcript')
@if(!empty($segments))
    <div>
        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">{{ $label }}</div>
        <div class="space-y-2">
            @foreach($segments as $seg)
                <div class="flex gap-3">
                    <span class="shrink-0 text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $seg[0] ?? '' }}</span>
                    <div class="min-w-0 max-w-[64ch]">
                        <span class="text-xs font-medium text-[color:var(--nx-muted)]">{{ $seg[1] ?? '' }}:</span>
                        <span class="text-[15px] leading-relaxed text-[color:var(--nx-text)] [overflow-wrap:anywhere]">{{ $seg[2] ?? '' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
