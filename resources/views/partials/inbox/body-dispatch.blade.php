{{-- Kanal-spezifischer Body — für Anker UND Facetten einer Einheit.
     $item: das (Detail-)Array, $inUnit: true, wenn als Facette unter einem Anker. --}}
@php($inUnit = $inUnit ?? false)
@switch($item['channel'] ?? null)
    @case('mail')
        @include('home::partials.inbox.body-mail', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @case('message')
        @include('home::partials.inbox.body-message', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @case('meeting')
        @include('home::partials.inbox.body-meeting', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @case('task')
        @include('home::partials.inbox.body-task', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @case('ticket')
        @include('home::partials.inbox.body-ticket', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @case('call')
        @include('home::partials.inbox.body-call', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @case('recording')
        @include('home::partials.inbox.body-recording', ['item' => $item, 'inUnit' => $inUnit])
    @break
    @default
        @include('home::partials.inbox.body-system', ['item' => $item, 'inUnit' => $inUnit])
@endswitch
