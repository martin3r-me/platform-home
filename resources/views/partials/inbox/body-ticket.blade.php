{{-- Ticket: dir zugewiesen — Beschreibung + Deep-Link ins Helpdesk --}}
<div class="space-y-4">
    @include('home::partials.inbox.prose', ['text' => $item['body'] ?? ''])

    @if(!empty($item['ticket_id']) && \Illuminate\Support\Facades\Route::has('helpdesk.tickets.show'))
        <div class="flex flex-wrap gap-2">
            <x-nx-button variant="primary" size="sm" :href="route('helpdesk.tickets.show', $item['ticket_id'])">
                @svg('heroicon-o-arrow-top-right-on-square', 'h-4 w-4')
                Ticket öffnen
            </x-nx-button>
        </div>
    @endif
</div>
