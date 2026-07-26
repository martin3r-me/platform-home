{{--
    Gemeinsamer Platzhalter für Home-Views (Struktur-Phase).
    Erwartet: $title (string), $icon (heroicon), $text (string).
    Optional: $aside = [ ['icon'=>, 'label'=>, 'text'=>], … ] — view-spezifische
    Sidebar-Inhalte (was in diesem Bereich zusammenläuft). KEINE generische
    Schnellzugriff-Sidebar mehr: die Sidebar gehört immer der jeweiligen View.
--}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$title" :icon="$icon" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar :title="$title" :icon="$icon" width="w-72" :defaultOpen="true" side="left">
            <div class="space-y-5 p-4">
                <p class="text-xs leading-relaxed text-[color:var(--nx-faint)]">{{ $text }}</p>

                @if(!empty($aside ?? []))
                    <div class="space-y-3">
                        @foreach($aside as $a)
                            <div class="flex items-start gap-2.5">
                                @svg($a['icon'] ?? 'heroicon-o-minus-small', 'mt-0.5 w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[color:var(--nx-text)]">{{ $a['label'] }}</div>
                                    @if(!empty($a['text']))
                                        <div class="mt-0.5 text-xs leading-relaxed text-[color:var(--nx-faint)]">{{ $a['text'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div><x-nx-badge variant="neutral">in Arbeit</x-nx-badge></div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container width="contained">
        <x-nx-empty :icon="$icon">
            {{ $text }}
        </x-nx-empty>
    </x-ui-page-container>
</x-ui-page>
