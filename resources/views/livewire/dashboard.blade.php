<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-squares-2x2" />
    </x-slot>

    <x-slot name="sidebar">
        @include('home::partials.inner-sidebar')
    </x-slot>

    <x-ui-page-container width="contained">
        {{-- Kopf --}}
        <div class="mb-10">
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">
                {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
            </h1>
            <p class="mt-1 text-sm text-[color:var(--nx-muted)]">
                Dein Überblick@if($personName) · <span class="text-[color:var(--nx-faint)]">verknüpft mit {{ $personName }}</span>@endif
            </p>
        </div>

        @php $hasData = !empty($vitalSigns) || !empty($responsibilities); @endphp

        @if(!$orgAvailable)
            <x-nx-callout variant="neutral" title="Kein Organisations-Kontext">
                Das organization-Modul ist hier nicht verfügbar — es liefert den Person-Knoten, aus dem dieses Dashboard speist.
            </x-nx-callout>
        @elseif(!$hasPerson)
            <x-nx-callout variant="info" title="Noch kein Person-Knoten verknüpft">
                Sobald dein Benutzer im organization-Modul mit einer Person-Entität verknüpft ist, laufen hier deine Aufgaben, Ziele und Kennzahlen zusammen.
            </x-nx-callout>
        @elseif(!$hasData)
            <x-nx-empty icon="heroicon-o-sparkles">
                Alles ruhig — aktuell laufen keine Kennzahlen oder Zuständigkeiten auf deinen Knoten auf.
            </x-nx-empty>
        @else
            <div class="space-y-10">
                {{-- Vital Signs --}}
                @foreach($vitalSigns as $sectionKey => $metrics)
                    @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                    <x-nx-section
                        :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')"
                        :title="$cfg['label'] ?? ucfirst($sectionKey)"
                        :description="$cfg['description'] ?? null">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach($metrics as $m)
                                <x-nx-stat
                                    :label="$m['label'] ?? ($m['key'] ?? '')"
                                    :value="$m['value'] ?? 0"
                                    :icon="'heroicon-o-' . ($cfg['icon'] ?? 'chart-bar')"
                                    :accent="$this->accentFor($m['variant'] ?? 'default')" />
                            @endforeach
                        </div>
                    </x-nx-section>
                @endforeach

                {{-- Zuständigkeiten --}}
                @foreach($responsibilities as $sectionKey => $groups)
                    @php
                        $cfg = $sectionConfigs[$sectionKey] ?? [];
                        $sectionTotal = 0;
                        foreach ($groups as $g) { $sectionTotal += $g['total_count'] ?? count($g['items'] ?? []); }
                    @endphp
                    <x-nx-section
                        :icon="'heroicon-o-' . ($cfg['icon'] ?? 'queue-list')"
                        :title="$cfg['label'] ?? ucfirst($sectionKey)"
                        :hint="$sectionTotal ?: null"
                        :description="$cfg['description'] ?? null">
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            @foreach($groups as $group)
                                @php
                                    $items = $group['items'] ?? [];
                                    $total = $group['total_count'] ?? count($items);
                                    $more  = max(0, $total - count($items));
                                @endphp
                                <x-nx-card flush>
                                    <div class="flex items-center justify-between gap-2 px-4 py-3">
                                        <div class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                            @svg('heroicon-o-' . ($group['icon'] ?? 'folder'), 'w-4 h-4 text-[color:var(--nx-muted)]')
                                            {{ $group['label'] ?? '' }}
                                        </div>
                                        <x-nx-badge variant="neutral">{{ $total }}</x-nx-badge>
                                    </div>

                                    @if(empty($items))
                                        <div class="px-4 pb-3 text-xs text-[color:var(--nx-faint)]">Nichts offen.</div>
                                    @else
                                        <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                                            @foreach($items as $item)
                                                <li>
                                                    <x-nx-list-item
                                                        :title="$item['name'] ?? '—'"
                                                        :meta="$item['meta'] ?? null"
                                                        :href="$item['url'] ?? null" />
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if($more > 0)
                                            <div class="border-t border-[color:var(--nx-line)] px-4 py-2 text-xs text-[color:var(--nx-faint)]">
                                                +{{ $more }} weitere
                                            </div>
                                        @endif
                                    @endif
                                </x-nx-card>
                            @endforeach
                        </div>
                    </x-nx-section>
                @endforeach
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
