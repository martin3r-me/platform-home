{{-- „Heute"-Schiene des Cockpits: orientierende Kontext-Spalte links neben dem
     Strom. Props kommen aus dem Trait ProvidesTodayRail; $eingangOpen/$eingangUnread
     reicht die Stream-View aus der tatsächlichen Liste durch. --}}
<div class="space-y-6 p-4">

    {{-- Kopf: Begrüßung + Datum (+ Person-Knoten) --}}
    <div>
        <div class="flex items-center gap-3">
            <x-nx-avatar :name="$personName ?: $firstName" :src="$avatar" size="lg" />
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-[color:var(--nx-text)]">
                    {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
                </div>
                <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ $dateLabel }}</div>
            </div>
        </div>
    </div>

    {{-- Check-in-Anstoß, wenn heute noch offen --}}
    @if(!$todayCheckin)
        <button type="button" x-data @click="$dispatch('open-modal-checkin')"
                class="flex w-full items-center gap-2 rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-accent-soft)] px-3 py-2 text-left text-xs font-medium text-[color:var(--nx-text)] transition-colors hover:bg-[color:var(--nx-active)]">
            @svg('heroicon-o-sun', 'h-4 w-4 shrink-0 text-[color:var(--nx-muted)]')
            <span class="min-w-0 flex-1">Noch kein Check-in heute — jetzt einchecken</span>
            @svg('heroicon-o-arrow-right', 'h-3.5 w-3.5 shrink-0')
        </button>
    @endif

    {{-- Heute auf einen Blick --}}
    <div>
        <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Heute</span>
        <div class="mt-1 space-y-0.5">
            <x-nx-property-row icon="heroicon-o-fire" label="Streak">{{ $streak > 0 ? $streak . ($streak === 1 ? ' Tag' : ' Tage') : '—' }}</x-nx-property-row>
            <x-nx-property-row icon="heroicon-o-check-circle" label="Check-in">{{ $todayCheckin ? 'erledigt' : 'offen' }}</x-nx-property-row>
            <x-nx-property-row icon="heroicon-o-calendar-days" label="Termine heute">{{ $todayEventCount }}</x-nx-property-row>
            <x-nx-property-row icon="heroicon-o-inbox" label="Eingang offen">{{ $eingangOpen }}{{ $eingangUnread > 0 ? ' · ' . $eingangUnread . ' neu' : '' }}</x-nx-property-row>
            @if($tasksAvailable && ($taskOverdue > 0 || $taskFrogs > 0))
                <a href="{{ route('home.aufgaben') }}" wire:navigate class="block rounded-md transition-colors hover:bg-[color:var(--nx-hover)]">
                    <x-nx-property-row icon="heroicon-o-bolt" label="Fokus">
                        @if($taskOverdue > 0)
                            <span class="text-[color:var(--nx-danger,var(--nx-text))]">{{ $taskOverdue }} überfällig</span>
                        @else
                            {{ $taskFrogs }} Frog{{ $taskFrogs === 1 ? '' : 's' }}
                        @endif
                    </x-nx-property-row>
                </a>
            @endif
        </div>
    </div>

    {{-- Termine heute — kurze Liste --}}
    @if($calendarAvailable && !empty($todayEvents))
        <div>
            <div class="flex items-center justify-between px-1">
                <span class="text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Termine</span>
                <a href="{{ route('home.kalender') }}" wire:navigate class="text-xs text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">Kalender</a>
            </div>
            <ul class="mt-1 space-y-0.5">
                @foreach($todayEvents as $e)
                    <li class="flex items-center gap-2 rounded-md px-1 py-1 {{ ($e['is_past'] ?? false) ? 'opacity-60' : '' }}">
                        <span class="w-11 shrink-0 text-[11px] font-medium tabular-nums text-[color:var(--nx-muted)]">{{ $e['time_label'] ?? '' }}</span>
                        <span class="min-w-0 flex-1 truncate text-xs text-[color:var(--nx-text)]">{{ $e['subject'] ?? 'Termin' }}</span>
                        @if(!empty($e['is_now']))
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-success)]"></span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Offene To-dos (Check-in) — abhakbar --}}
    @if(count($openTodos) > 0)
        <div>
            <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Offene To-dos</span>
            <ul class="mt-1 space-y-0.5">
                @foreach($openTodos as $todo)
                    <li>
                        <button type="button" wire:click="toggleTodo({{ $todo['id'] }})"
                                class="flex w-full items-center gap-2 rounded-md px-1 py-1 text-left transition-colors hover:bg-[color:var(--nx-hover)]"
                                aria-label="Als erledigt markieren">
                            <span class="h-4 w-4 shrink-0 rounded-[5px] border border-[color:var(--nx-line-strong,var(--nx-line))]"></span>
                            <span class="min-w-0 flex-1 truncate text-xs text-[color:var(--nx-text)]">{{ $todo['title'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Knoten-KPIs (Vital Signs) — kompakt --}}
    @if($orgAvailable && $hasPerson && !empty($vitalSigns))
        <div>
            <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Auf einen Blick</span>
            <div class="mt-1 space-y-0.5">
                @foreach($vitalSigns as $sectionKey => $metrics)
                    @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                    @foreach($metrics as $m)
                        @php $href = $this->kpiHref($sectionKey); @endphp
                        @if($href)
                            <a href="{{ $href }}" wire:navigate class="block rounded-md transition-colors hover:bg-[color:var(--nx-hover)]">
                                <x-nx-property-row :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')" :label="$m['label'] ?? ''">{{ $m['value'] ?? 0 }}</x-nx-property-row>
                            </a>
                        @else
                            <x-nx-property-row :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')" :label="$m['label'] ?? ''">{{ $m['value'] ?? 0 }}</x-nx-property-row>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- Überfällige Pflichtkurse — nur wenn vorhanden --}}
    @if($courseOverdue > 0)
        <a href="{{ route('home.kurse') }}" wire:navigate
           class="flex items-center gap-2 rounded-[10px] border border-[color:var(--nx-line)] px-3 py-2 text-xs font-medium text-[color:var(--nx-text)] transition-colors hover:bg-[color:var(--nx-hover)]">
            @svg('heroicon-o-academic-cap', 'h-4 w-4 shrink-0 text-[color:var(--nx-danger,var(--nx-muted))]')
            <span class="min-w-0 flex-1">{{ $courseOverdue }} überfällige{{ $courseOverdue === 1 ? 'r Kurs' : ' Kurse' }}</span>
        </a>
    @endif

    @include('home::partials.quick-actions')
</div>
