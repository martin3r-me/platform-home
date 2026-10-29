{{-- Widget: Zuletzt gestempelt (organization PersonTimeSummary). Kein Live-Timer
     im Backend → ehrlich der jüngste Eintrag + 7-Tage-Mini-Chart. --}}
<x-home::widget title="Zuletzt gestempelt" icon="heroicon-o-clock" :href="route('home.zeiten')" action="Zeiten">
    <div class="px-4 py-3">
        @if(!$timeAvailable)
            <p class="text-xs text-[color:var(--nx-faint)]">Zeiterfassung nicht verbunden.</p>
        @elseif($lastStamp)
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $lastStamp['context'] }}</div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-[color:var(--nx-muted)]">
                        <span class="font-medium text-[color:var(--nx-text)]">{{ $lastStamp['hours_label'] }}</span>
                        <span>·</span>
                        <span>{{ $lastStamp['when'] }}</span>
                        @if($lastStamp['is_billed'])
                            <span class="rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium">abgerechnet</span>
                        @endif
                    </div>
                    @if($lastStamp['note'] !== '')
                        <p class="mt-1 line-clamp-2 text-xs text-[color:var(--nx-faint)]">{{ $lastStamp['note'] }}</p>
                    @endif
                </div>
            </div>
        @else
            <p class="text-xs text-[color:var(--nx-faint)]">Noch nichts gestempelt.</p>
        @endif

        {{-- 7-Tage-Mini-Chart --}}
        @if(!empty($timeByDay))
            <div class="mt-3 border-t border-[color:var(--nx-line)] pt-3">
                <div class="mb-2 flex items-baseline justify-between">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Letzte 7 Tage</span>
                    @if($time7dLabel !== '')
                        <span class="text-xs font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $time7dLabel }}</span>
                    @endif
                </div>
                <div class="flex items-end gap-1.5" style="height: 48px;">
                    @foreach($timeByDay as $day)
                        @php $barPx = $timeMaxMinutes > 0 ? max(2, (int) round(($day['minutes'] / $timeMaxMinutes) * 40)) : 2; @endphp
                        <div class="flex flex-1 flex-col items-center justify-end gap-1">
                            <div class="w-full rounded-[2px] {{ $day['minutes'] > 0 ? 'bg-[color:var(--nx-accent)]' : 'bg-[color:var(--nx-accent-soft)]' }}" style="height: {{ $barPx }}px;"></div>
                            <span class="text-[9px] uppercase text-[color:var(--nx-faint)]">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-home::widget>
