@include('home::partials.placeholder', [
    'title' => 'Benachrichtigungen',
    'icon'  => 'heroicon-o-bell',
    'text'  => 'Benachrichtigungen — persönliche Inbox & Erwähnungen.',
    'aside' => [
        ['icon' => 'heroicon-o-at-symbol', 'label' => 'Erwähnungen', 'text' => 'wo du direkt angesprochen wirst'],
        ['icon' => 'heroicon-o-squares-2x2', 'label' => 'Nach Modul', 'text' => 'gebündelt statt Einzel-Rauschen'],
        ['icon' => 'heroicon-o-envelope-open', 'label' => 'Ungelesen zuerst', 'text' => 'lesen, erledigen, weglegen'],
    ],
])
