@include('home::partials.placeholder', [
    'title' => 'Profil & Einstellungen',
    'icon'  => 'heroicon-o-user-circle',
    'text'  => 'Profil & Einstellungen — persönliche Kontoeinstellungen.',
    'aside' => [
        ['icon' => 'heroicon-o-identification', 'label' => 'Konto', 'text' => 'Name, Avatar, Kontaktdaten'],
        ['icon' => 'heroicon-o-shield-check', 'label' => 'Sicherheit', 'text' => 'Passwort & Anmeldung'],
        ['icon' => 'heroicon-o-adjustments-horizontal', 'label' => 'Präferenzen', 'text' => 'Sprache, Benachrichtigungen, Darstellung'],
    ],
])
