{{--
    Home – generische innere (kontextuelle) Sidebar, gerendert im x-slot "sidebar".
    Getrennt von der Haupt-Nav-Leiste (home.sidebar). Standard: Schnellzugriff.
--}}
<x-ui-page-sidebar title="Schnellzugriff" icon="heroicon-o-bolt" width="w-72" :defaultOpen="true" side="left">
    <div class="p-4">
        @include('home::partials.quick-actions')
    </div>
</x-ui-page-sidebar>
