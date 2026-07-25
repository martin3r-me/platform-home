{{-- Aufgabe: Properties + Definition of Done --}}
<div class="space-y-4">
    <div class="space-y-0.5">
        @foreach($item['props'] ?? [] as $p)
            <x-nx-property-row :icon="$p[2] ?? null" :label="$p[0]">{{ $p[1] }}</x-nx-property-row>
        @endforeach
    </div>

    <div>
        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Definition of Done</div>
        <ul class="space-y-1.5">
            @foreach($item['dod'] ?? [] as $d)
                <li class="flex items-center gap-2 text-sm text-[color:var(--nx-text)]">
                    <span class="h-4 w-4 shrink-0 rounded border border-[color:var(--nx-line-strong)]"></span>{{ $d }}
                </li>
            @endforeach
        </ul>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-nx-button variant="primary" size="sm">Übernehmen</x-nx-button>
        <x-nx-button variant="ghost" size="sm">Im Helpdesk öffnen</x-nx-button>
    </div>
</div>
