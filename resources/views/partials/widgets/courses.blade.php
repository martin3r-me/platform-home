{{-- Widget: Pflichtkurse (academy AcademyAssignmentService) — offene mit Fortschritt. --}}
<x-home::widget title="Pflichtkurse" icon="heroicon-o-academic-cap"
    :href="route('home.kurse')" action="Alle"
    :badge="$courseOverdue > 0 ? $courseOverdue . ' überfällig' : ($courseOpen > 0 ? (string) $courseOpen : null)"
    :badgeClass="$courseOverdue > 0 ? 'text-[color:var(--nx-danger,var(--nx-text))]' : ''">

    @if(!$coursesAvailable)
        <div class="px-4 py-3"><p class="text-xs text-[color:var(--nx-faint)]">Academy nicht verbunden.</p></div>
    @elseif(empty($courseItems))
        <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-check-circle">Keine offenen Pflichtkurse.</x-nx-empty></div>
    @else
        <ul class="divide-y divide-[color:var(--nx-line)]">
            @foreach($courseItems as $c)
                @php $pct = max(0, min(100, (int) ($c['progress_pct'] ?? 0))); @endphp
                <li class="px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-book-open', 'h-4 w-4 shrink-0 ' . (($c['is_overdue'] ?? false) ? 'text-[color:var(--nx-danger,var(--nx-faint))]' : 'text-[color:var(--nx-faint)]'))
                        <div class="min-w-0 flex-1 truncate text-sm text-[color:var(--nx-text)]">{{ $c['title'] ?? 'Kurs' }}</div>
                        <span class="shrink-0 text-xs tabular-nums text-[color:var(--nx-muted)]">{{ $pct }}%</span>
                    </div>
                    <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-[color:var(--nx-accent-soft)]">
                        <div class="h-full rounded-full bg-[color:var(--nx-accent)]" style="width: {{ $pct }}%;"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-home::widget>
