{{-- Agenda-Karte (Planner-Stil): Farbband links, Titel, Meta-Zeile; Klick = bearbeiten --}}
@php
    $edge = match (true) {
        $item['is_overdue'] => 'var(--nx-danger)',
        $item['is_done']    => 'var(--nx-success)',
        $item['is_linked']  => 'var(--nx-info)',
        default             => 'var(--nx-line-strong)',
    };
    $statusVariant = match ($item['status_color'] ?? '') {
        'green' => 'success', 'red' => 'danger', 'yellow', 'orange' => 'warning', 'blue' => 'info', default => 'neutral',
    };
@endphp

<x-nx-kanban-card :title="''" :sortable-id="$item['id']" wire:key="agenda-item-{{ $item['id'] }}"
    class="group/card relative cursor-pointer {{ $item['is_done'] ? 'opacity-60' : '' }}">
  {{-- eigener Scope: die Karte hat schon ein x-data (Board/Liste); Klick nur ohne Drag --}}
  <div x-data="{ press: null }"
       @mousedown="press = { x: $event.clientX, y: $event.clientY }"
       @click="if (press && Math.abs($event.clientX - press.x) < 5 && Math.abs($event.clientY - press.y) < 5) $wire.editItem({{ $item['id'] }}); press = null">

    <div class="absolute top-2.5 bottom-2.5 left-1.5 w-[3px] rounded-full" style="background-color: {{ $edge }};"></div>

    <div class="flex items-start gap-2 pl-3 pr-6">
        <h4 class="m-0 text-[13px] font-semibold leading-snug text-[color:var(--nx-text)] {{ $item['is_done'] ? 'line-through text-[color:var(--nx-muted)]' : '' }}">
            {{ $item['title'] }}
        </h4>

        {{-- Schnell-Erledigt (Hover); stoppt den Karten-Klick --}}
        <button type="button"
            @mousedown.stop @click.stop="$wire.toggleDone({{ $item['id'] }})"
            class="absolute top-2 right-2 inline-flex h-5 w-5 items-center justify-center rounded-full transition-opacity {{ $item['is_done'] ? 'bg-[color:var(--nx-success)] text-white' : 'opacity-0 group-hover/card:opacity-100 border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] text-[color:var(--nx-muted)] hover:border-[color:var(--nx-success)] hover:text-[color:var(--nx-success)]' }}"
            title="{{ $item['is_done'] ? 'Wieder öffnen' : 'Erledigt' }}">
            @svg('heroicon-s-check', 'w-3 h-3')
        </button>
    </div>

    @if($item['description'])
        <p class="mt-1 mb-0 line-clamp-2 pl-3 text-[11px] leading-snug text-[color:var(--nx-muted)]">{{ $item['description'] }}</p>
    @endif

    @if($item['is_linked'] || $item['date_label'] || $item['status'] || $item['due_date'])
        <div class="mt-2 flex items-center gap-1.5 pl-3 text-[10px] leading-none text-[color:var(--nx-muted)]">
            @if($item['is_linked'])
                <span class="inline-flex shrink-0 items-center gap-0.5 {{ $item['missing'] ? 'line-through opacity-60' : '' }}"
                      title="{{ $item['missing'] ? 'Verknüpftes Objekt nicht mehr vorhanden' : 'Verknüpft' }}">
                    @svg('heroicon-o-link', 'w-3 h-3 opacity-60')
                    {{ $item['type_label'] }}
                </span>
            @endif
            @if($item['status'])
                <x-nx-badge :variant="$statusVariant" class="!px-1.5 !py-0 !text-[10px]">{{ $item['status'] }}</x-nx-badge>
            @endif
            @if($item['date_label'])
                <span class="inline-flex shrink-0 items-center gap-0.5 {{ $item['is_overdue'] ? 'font-medium text-[color:var(--nx-danger)]' : '' }}">
                    @svg('heroicon-o-calendar', 'w-3 h-3 opacity-60')
                    {{ $item['date_label'] }}@if($item['time_start']) · {{ $item['time_start'] }}@endif
                </span>
            @elseif($item['due_date'])
                <span class="inline-flex shrink-0 items-center gap-0.5" title="Fällig">
                    @svg('heroicon-o-clock', 'w-3 h-3 opacity-60')
                    {{ \Illuminate\Support\Carbon::parse($item['due_date'])->format('d.m.') }}
                </span>
            @endif

            <span class="flex-1"></span>

            @if($item['url'])
                <a href="{{ $item['url'] }}" wire:navigate @mousedown.stop @click.stop
                   class="shrink-0 rounded p-0.5 text-[color:var(--nx-faint)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                   title="Öffnen">
                    @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')
                </a>
            @endif
        </div>
    @endif
  </div>
</x-nx-kanban-card>
