{{-- System: Ereignis + Aktion --}}
<div class="space-y-4">
    @include('home::partials.inbox.prose', ['text' => $item['body'] ?? ''])
    <div class="flex flex-wrap gap-2">
        <x-nx-button variant="primary" size="sm">{{ $item['action_label'] ?? 'Öffnen' }}</x-nx-button>
    </div>
</div>
