<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meine Aufgaben" icon="heroicon-o-clipboard-document-check" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Überblick" icon="heroicon-o-clipboard-document-check" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-6 p-4">
                <div>
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Offen</span>
                    <div class="mt-1 space-y-0.5">
                        <x-nx-property-row icon="heroicon-o-clipboard-document-check" label="Gesamt">{{ $total }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-exclamation-triangle" label="Überfällig">{{ $overdue }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-bolt" label="Frogs">{{ $frogs }}</x-nx-property-row>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="mb-8 flex items-baseline justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">Meine Aufgaben</h1>
            <span class="text-sm text-[color:var(--nx-muted)] tabular-nums">{{ $total }} offen</span>
        </div>

        @if(!$available)
            <x-nx-callout variant="neutral" title="Kein Aufgaben-Kontext">
                Das planner-Modul liefert deine Aufgaben — hier ist es nicht verfügbar.
            </x-nx-callout>
        @elseif(empty($items))
            <x-nx-empty icon="heroicon-o-check-circle">
                Keine offenen Aufgaben — alles erledigt. 🎉
            </x-nx-empty>
        @else
            @php
                $frogItems = array_values(array_filter($items, fn ($i) => $i['is_frog']));
                $restItems = array_values(array_filter($items, fn ($i) => !$i['is_frog']));
            @endphp

            <div class="space-y-8">
                @if(!empty($frogItems))
                    <x-nx-section icon="heroicon-o-bolt" title="Frogs" hint="zuerst">
                        <x-nx-card flush>
                            <ul class="divide-y divide-[color:var(--nx-line)]">
                                @foreach($frogItems as $t)
                                    @include('home::partials.task-row', ['t' => $t])
                                @endforeach
                            </ul>
                        </x-nx-card>
                    </x-nx-section>
                @endif

                @if(!empty($restItems))
                    <x-nx-section icon="heroicon-o-list-bullet" title="Offen" :hint="(string) count($restItems)">
                        <x-nx-card flush>
                            <ul class="divide-y divide-[color:var(--nx-line)]">
                                @foreach($restItems as $t)
                                    @include('home::partials.task-row', ['t' => $t])
                                @endforeach
                            </ul>
                        </x-nx-card>
                    </x-nx-section>
                @endif
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
