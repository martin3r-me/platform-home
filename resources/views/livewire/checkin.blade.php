@include('home::partials.placeholder', [
    'title' => 'Checkin',
    'icon'  => 'heroicon-o-check-circle',
    'text'  => 'Checkin — hier entsteht der tägliche Check-in inkl. Verlauf & Trends.',
    'aside' => [
        ['icon' => 'heroicon-o-flag', 'label' => 'Tagesziel', 'text' => 'Was heute zählt — ein Satz'],
        ['icon' => 'heroicon-o-face-smile', 'label' => 'Stimmung & Energie', 'text' => 'kurz festhalten, wie es dir geht'],
        ['icon' => 'heroicon-o-chart-bar', 'label' => 'Verlauf & Trends', 'text' => 'Streak und Muster über die Wochen'],
    ],
])
