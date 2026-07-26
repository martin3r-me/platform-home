@include('home::partials.placeholder', [
    'title' => 'Notizen',
    'icon'  => 'heroicon-o-pencil-square',
    'text'  => 'Notizen — schnelle persönliche Notizen.',
    'aside' => [
        ['icon' => 'heroicon-o-bolt', 'label' => 'Schnell festhalten', 'text' => 'Gedanken, bevor sie weg sind'],
        ['icon' => 'heroicon-o-folder', 'label' => 'Ordner & Tags', 'text' => 'ordnen, wiederfinden, anpinnen'],
        ['icon' => 'heroicon-o-puzzle-piece', 'label' => 'notes-Modul nötig', 'text' => 'in taiste noch nicht eingebunden'],
    ],
])
