@include('home::partials.placeholder', [
    'title' => 'Agenda',
    'icon'  => 'heroicon-o-queue-list',
    'text'  => 'Agenda — hier landen modulübergreifende Dinge zum Sortieren & Weglegen.',
    'aside' => [
        ['icon' => 'heroicon-o-inbox-stack', 'label' => 'Was zusammenläuft', 'text' => 'Aufgaben, Tickets & Erwähnungen aus allen Modulen'],
        ['icon' => 'heroicon-o-arrow-down-on-square', 'label' => 'Sortieren & weglegen', 'text' => 'triagieren bis leer — jedes Ding an seinen Ort'],
        ['icon' => 'heroicon-o-share', 'label' => 'An Knoten hängen', 'text' => 'Relevantes in die Organisation einhängen'],
    ],
])
