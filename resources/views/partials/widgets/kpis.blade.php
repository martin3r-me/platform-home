{{-- Widget: Auf einen Blick — Knoten-KPIs (organization PersonActivityRegistry). --}}
<x-home::widget title="Auf einen Blick" icon="heroicon-o-squares-2x2">
    <div class="grid grid-cols-2 gap-px bg-[color:var(--nx-line)]">
        @foreach($vitalSigns as $sectionKey => $metrics)
            @php $cfg = $sectionConfigs[$sectionKey] ?? []; @endphp
            @foreach($metrics as $m)
                @php
                    $variant = $m['variant'] ?? 'default';
                    $valueClass = match ($variant) {
                        'danger'  => 'text-[color:var(--nx-danger,var(--nx-text))]',
                        'warning' => 'text-[color:var(--nx-warning,var(--nx-text))]',
                        'success' => 'text-[color:var(--nx-success)]',
                        default   => 'text-[color:var(--nx-text)]',
                    };
                    $href = $this->kpiHref($sectionKey);
                @endphp
                <{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" wire:navigate @endif
                    class="flex flex-col gap-0.5 bg-[color:var(--nx-surface)] px-4 py-3 {{ $href ? 'transition-colors hover:bg-[color:var(--nx-hover)]' : '' }}">
                    <span class="flex items-center gap-1.5 text-[11px] text-[color:var(--nx-faint)]">
                        @svg('heroicon-o-' . ($cfg['icon'] ?? 'chart-bar'), 'h-3.5 w-3.5')
                        <span class="truncate">{{ $m['label'] ?? '' }}</span>
                    </span>
                    <span class="text-lg font-semibold tabular-nums {{ $valueClass }}">{{ $m['value'] ?? 0 }}</span>
                </{{ $href ? 'a' : 'div' }}>
            @endforeach
        @endforeach
    </div>
</x-home::widget>
