{{-- Aufgabe: dir zugewiesen — Beschreibung (+ Prototyp-Properties/DoD, falls da) --}}
<div class="space-y-4">
    @if(!empty($item['body']))
        <div class="whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-text)]">{{ $item['body'] }}</div>
    @endif

    @if(!empty($item['props']))
        <div class="space-y-0.5">
            @foreach($item['props'] as $p)
                <x-nx-property-row :icon="$p[2] ?? null" :label="$p[0]">{{ $p[1] }}</x-nx-property-row>
            @endforeach
        </div>
    @endif

    @if(!empty($item['dod']))
        <div>
            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Definition of Done</div>
            <ul class="space-y-1.5">
                @foreach($item['dod'] as $d)
                    <li class="flex items-center gap-2 text-sm text-[color:var(--nx-text)]">
                        <span class="h-4 w-4 shrink-0 rounded border border-[color:var(--nx-line-strong)]"></span>{{ $d }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Deep-Link zur Aufgabe (loose: Route-Name + id, guarded) --}}
    @if(!empty($item['task_id']) && \Illuminate\Support\Facades\Route::has('planner.tasks.show'))
        <div class="flex flex-wrap gap-2">
            <x-nx-button variant="primary" size="sm" :href="route('planner.tasks.show', $item['task_id'])">
                @svg('heroicon-o-arrow-top-right-on-square', 'h-4 w-4')
                Aufgabe öffnen
            </x-nx-button>
        </div>
    @endif
</div>
