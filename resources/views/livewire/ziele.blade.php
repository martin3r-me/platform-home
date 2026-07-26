<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meine Ziele" icon="heroicon-o-flag" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Überblick" icon="heroicon-o-flag" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-6 p-4">
                <div>
                    <span class="px-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Objectives</span>
                    <div class="mt-1 space-y-0.5">
                        <x-nx-property-row icon="heroicon-o-flag" label="Gesamt">{{ $total }}</x-nx-property-row>
                        <x-nx-property-row icon="heroicon-o-arrow-trending-up" label="Mountains">{{ $mountains }}</x-nx-property-row>
                        @if($avgLabel)
                            <x-nx-property-row icon="heroicon-o-chart-bar" label="Ø Fortschritt">{{ $avgLabel }}</x-nx-property-row>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="mb-8 flex items-baseline justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">Meine Ziele</h1>
            <span class="text-sm text-[color:var(--nx-muted)] tabular-nums">{{ $total }} {{ $total === 1 ? 'Ziel' : 'Ziele' }}</span>
        </div>

        @if(!$available)
            <x-nx-callout variant="neutral" title="Kein Ziel-Kontext">
                Das okr-Modul liefert deine Ziele — hier ist es nicht verfügbar.
            </x-nx-callout>
        @elseif(empty($items))
            <x-nx-empty icon="heroicon-o-flag">
                Noch keine Ziele, für die du verantwortlich bist.
            </x-nx-empty>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($items as $o)
                    @if($o['url'])<a href="{{ $o['url'] }}" wire:navigate class="block">@endif
                    <x-nx-card :hover="(bool) $o['url']">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    @if($o['is_mountain'])
                                        @svg('heroicon-o-arrow-trending-up', 'w-4 h-4 shrink-0 text-[color:var(--nx-accent)]')
                                    @else
                                        @svg('heroicon-o-flag', 'w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                                    @endif
                                    <span class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $o['title'] }}</span>
                                </div>
                                @if($o['cycle'])
                                    <div class="mt-0.5 text-xs text-[color:var(--nx-faint)]">{{ $o['cycle'] }}</div>
                                @endif
                            </div>
                            @if($o['score_label'])
                                <x-nx-badge :variant="$o['score_variant']">{{ $o['score_label'] }}</x-nx-badge>
                            @endif
                        </div>

                        @if($o['description'])
                            <p class="mt-2 text-xs leading-relaxed text-[color:var(--nx-faint)]">{{ $o['description'] }}</p>
                        @endif

                        @if($o['score'] !== null)
                            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-[color:var(--nx-accent-soft)]">
                                <div class="h-full rounded-full bg-[color:var(--nx-accent)]" style="width: {{ max(2, min(100, (int) round($o['score'] * 100))) }}%;"></div>
                            </div>
                        @endif
                    </x-nx-card>
                    @if($o['url'])</a>@endif
                @endforeach
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
