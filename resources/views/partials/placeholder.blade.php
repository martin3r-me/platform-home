{{--
    Gemeinsamer Platzhalter für Home-Views (Struktur-Phase).
    Erwartet: $title (string), $icon (heroicon), $text (string).
--}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$title" :icon="$icon" />
    </x-slot>

    <x-slot name="sidebar">
        @include('home::partials.inner-sidebar')
    </x-slot>

    <x-ui-page-container width="contained">
        <x-nx-empty :icon="$icon">
            {{ $text }}
        </x-nx-empty>
    </x-ui-page-container>
</x-ui-page>
