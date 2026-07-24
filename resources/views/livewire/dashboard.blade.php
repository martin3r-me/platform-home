<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-squares-2x2" />
    </x-slot>

    <x-slot name="sidebar">
        @include('home::partials.sidebar')
    </x-slot>

    <x-ui-page-container width="contained">
        {{-- Begrüßung --}}
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-[color:var(--nx-text)]">
                {{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}
            </h1>
            @if($personName)
                <p class="mt-1 text-sm text-[color:var(--nx-muted)]">
                    Dein Überblick · verknüpft mit <span class="text-[color:var(--nx-text)]">{{ $personName }}</span>
                </p>
            @endif
        </div>

        @php
            $hasData = !empty($vitalSigns) || !empty($responsibilities);
        @endphp

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
            <div class="space-y-8">
                {{-- Vital Signs --}}
                @if(!empty($vitalSigns))
                    <div class="space-y-5">
                        @foreach($vitalSigns as $sectionKey => $metrics)
                            @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                            <div>
                                <div class="mb-3 flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                                    @svg($cfg['icon'] ?? 'heroicon-o-chart-bar', 'w-4 h-4')
                                    {{ $cfg['label'] ?? ucfirst($sectionKey) }}
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach($metrics as $m)
                                        <x-nx-stat
                                            :label="$m['label'] ?? ($m['key'] ?? '')"
                                            :value="$m['value'] ?? 0"
                                            :icon="$cfg['icon'] ?? 'heroicon-o-chart-bar'"
                                            :accent="$this->accentFor($m['variant'] ?? 'default')" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Responsibilities --}}
                @if(!empty($responsibilities))
                    <div class="space-y-5">
                        @foreach($responsibilities as $sectionKey => $groups)
                            @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
                            <div>
                                <div class="mb-3 flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                                    @svg($cfg['icon'] ?? 'heroicon-o-queue-list', 'w-4 h-4')
                                    {{ $cfg['label'] ?? ucfirst($sectionKey) }}
                                </div>

                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                    @foreach($groups as $group)
                                        <x-nx-card flush>
                                            <div class="flex items-center justify-between gap-2 px-4 py-3">
                                                <div class="flex items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
                                                    @svg($group['icon'] ?? 'heroicon-o-folder', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                                    {{ $group['label'] ?? '' }}
                                                </div>
                                                <x-nx-badge variant="neutral">{{ $group['total_count'] ?? count($group['items'] ?? []) }}</x-nx-badge>
                                            </div>

                                            @if(empty($group['items']))
                                                <div class="px-4 pb-3 text-xs text-[color:var(--nx-faint)]">Nichts offen.</div>
                                            @else
                                                <ul class="divide-y divide-[color:var(--nx-line)] border-t border-[color:var(--nx-line)]">
                                                    @foreach($group['items'] as $item)
                                                        <li>
                                                            @php $url = $item['url'] ?? null; @endphp
                                                            <a @if($url) href="{{ $url }}" @endif
                                                               class="flex items-center gap-3 px-4 py-2.5 transition-colors @if($url) hover:bg-[color:var(--nx-hover)] @endif">
                                                                <span class="min-w-0 flex-1 truncate text-sm text-[color:var(--nx-text)]">{{ $item['name'] ?? '—' }}</span>
                                                                @if(!empty($item['meta']))
                                                                    <span class="shrink-0 text-xs text-[color:var(--nx-faint)]">{{ $item['meta'] }}</span>
                                                                @endif
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </x-nx-card>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
