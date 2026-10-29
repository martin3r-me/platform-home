{{-- Widget: Offene Aufgaben (planner PersonTaskSummary), Frogs/überfällig zuerst. --}}
<x-home::widget title="Offene Aufgaben" icon="heroicon-o-bolt"
    :href="route('home.aufgaben')" :action="'Alle' . ($taskTotal > 0 ? ' · ' . $taskTotal : '')"
    :badge="$taskOverdue > 0 ? $taskOverdue . ' überfällig' : ($taskFrogs > 0 ? $taskFrogs . ' Frog' . ($taskFrogs === 1 ? '' : 's') : null)"
    :badgeClass="$taskOverdue > 0 ? 'text-[color:var(--nx-danger,var(--nx-text))]' : ''">

    @if(!$tasksAvailable)
        <div class="px-4 py-3"><p class="text-xs text-[color:var(--nx-faint)]">Aufgaben nicht verbunden.</p></div>
    @elseif(empty($taskItems))
        <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-check-circle">Keine offenen Aufgaben — stark.</x-nx-empty></div>
    @else
        <ul class="divide-y divide-[color:var(--nx-line)]">
            @foreach($taskItems as $t)
                <li class="flex items-start gap-3 px-4 py-2.5">
                    @svg(($t['is_frog'] ?? false) ? 'heroicon-o-fire' : 'heroicon-o-check-circle', 'mt-0.5 h-4 w-4 shrink-0 ' . (($t['is_overdue'] ?? false) ? 'text-[color:var(--nx-danger,var(--nx-faint))]' : 'text-[color:var(--nx-faint)]'))
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $t['title'] ?? 'Aufgabe' }}</div>
                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-[color:var(--nx-faint)]">
                            @if(!empty($t['project']))<span class="truncate">{{ $t['project'] }}</span>@endif
                            @if(!empty($t['due_label']))
                                @if(!empty($t['project']))<span>·</span>@endif
                                <span class="{{ ($t['is_overdue'] ?? false) ? 'text-[color:var(--nx-danger,var(--nx-muted))]' : '' }}">{{ $t['due_label'] }}</span>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-home::widget>
