{{-- Widget: Termine heute (user-connectors PersonCalendarService). --}}
<x-home::widget title="Termine heute" icon="heroicon-o-calendar-days"
    :href="route('home.kalender')" action="Kalender"
    :badge="$todayEventCount > 0 ? (string) $todayEventCount : null">

    @if(!$calendarAvailable)
        <div class="px-4 py-3"><p class="text-xs text-[color:var(--nx-faint)]">Kein Kalender-Connector verbunden.</p></div>
    @elseif(empty($todayEvents))
        <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-calendar">Keine Termine heute — freie Bahn.</x-nx-empty></div>
    @else
        <ul class="divide-y divide-[color:var(--nx-line)]">
            @foreach($todayEvents as $e)
                <li class="flex items-center gap-3 px-4 py-2.5 {{ ($e['is_past'] ?? false) ? 'opacity-60' : '' }}">
                    <span class="w-14 shrink-0 text-xs font-medium tabular-nums text-[color:var(--nx-muted)]">{{ $e['time_label'] ?? '' }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $e['subject'] ?? 'Termin' }}</div>
                        @if(!empty($e['location']) || !empty($e['is_online']))
                            <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ !empty($e['is_online']) ? 'Online' : $e['location'] }}</div>
                        @endif
                    </div>
                    @if(!empty($e['is_now']))
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-success)]"></span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-home::widget>
