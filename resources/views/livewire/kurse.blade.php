<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meine Kurse" icon="heroicon-o-academic-cap" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" icon="heroicon-o-academic-cap" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-1 p-4">
                <x-nx-property-row icon="heroicon-o-rectangle-stack" label="Pflichtkurse">{{ $total }}</x-nx-property-row>
                <x-nx-property-row icon="heroicon-o-check-circle" label="Erledigt">{{ $done }}</x-nx-property-row>
                <x-nx-property-row icon="heroicon-o-ellipsis-horizontal-circle" label="Offen">{{ $open }}</x-nx-property-row>
                <x-nx-property-row icon="heroicon-o-exclamation-triangle" label="Überfällig">{{ $overdue }}</x-nx-property-row>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="mb-8 flex items-baseline justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">Meine Kurse</h1>
            <span class="text-sm text-[color:var(--nx-muted)] tabular-nums">{{ $done }}/{{ $total }} erledigt</span>
        </div>

        @if(!$available)
            <x-nx-callout variant="neutral" title="Academy nicht verfügbar">
                Das academy-Modul liefert die Pflichtkurse — hier ist es nicht verfügbar.
            </x-nx-callout>
        @elseif($overdue > 0)
            <x-nx-callout variant="danger" title="Überfällige Pflichtkurse" icon="heroicon-o-exclamation-triangle" class="mb-6">
                {{ $overdue }} Pflichtkurs(e) sind überfällig. Bitte zeitnah abschließen.
            </x-nx-callout>
        @endif

        @if($available)
            @if(empty($courses))
                <x-nx-empty icon="heroicon-o-academic-cap">
                    Dir sind aktuell keine Pflichtkurse zugewiesen.
                </x-nx-empty>
            @else
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach($courses as $c)
                        <x-nx-card>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    @if($c['url'])
                                        <a href="{{ $c['url'] }}" wire:navigate class="text-sm font-semibold text-[color:var(--nx-text)] hover:underline">{{ $c['title'] }}</a>
                                    @else
                                        <span class="text-sm font-semibold text-[color:var(--nx-text)]">{{ $c['title'] }}</span>
                                    @endif
                                    @if($c['due_label'])
                                        <div class="mt-0.5 text-xs text-[color:var(--nx-faint)]">{{ $c['due_label'] }}</div>
                                    @endif
                                </div>
                                <x-nx-badge :variant="$c['status_variant']">{{ $c['status_label'] }}</x-nx-badge>
                            </div>

                            <div class="mt-4">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[color:var(--nx-accent-soft)]">
                                    <div class="h-full rounded-full bg-[color:var(--nx-accent)]" style="width: {{ $c['progress_pct'] }}%;"></div>
                                </div>
                                <div class="mt-1 text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $c['progress_pct'] }}%</div>
                            </div>
                        </x-nx-card>
                    @endforeach
                </div>
            @endif
        @endif
    </x-ui-page-container>
</x-ui-page>
