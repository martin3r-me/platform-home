@props([
    'title' => '',
    'icon' => null,
    'href' => null,        // optionaler „mehr"-Link rechts im Kopf
    'action' => null,      // Label des Links
    'badge' => null,       // optionaler Zähler/Status rechts vom Titel
    'badgeClass' => '',     // Overrides für das Badge (Default: accent-soft)
])

{{-- Gemeinsame Hülle aller Zuhause-Widgets: eine ruhige Karte mit Kopf (Icon +
     Titel + optionalem Badge + optionalem Link) und einem Body-Slot. Ein Widget =
     ein Person-Kontrakt. Einheitlich, damit der spätere Abo-Katalog nur noch
     Bausteine ein-/ausblenden muss. --}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-[14px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] shadow-sm']) }}>
    <header class="flex items-center justify-between gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
        <h2 class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[color:var(--nx-text)]">
            @if($icon)
                @svg($icon, 'h-4 w-4 shrink-0 text-[color:var(--nx-muted)]')
            @endif
            <span class="truncate">{{ $title }}</span>
            @if($badge !== null && $badge !== '')
                <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $badgeClass ?: 'bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]' }}">{{ $badge }}</span>
            @endif
        </h2>
        @if($href && $action)
            <a href="{{ $href }}" wire:navigate class="shrink-0 text-xs font-medium text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">{{ $action }}</a>
        @endif
    </header>
    <div>
        {{ $slot }}
    </div>
</section>
