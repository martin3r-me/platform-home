{{-- Agenda – Übersicht meiner Agenden; jede öffnet als Kanban-Board --}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Agenda" icon="heroicon-o-queue-list" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[['label' => 'Agenda', 'icon' => 'queue-list']]" />
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="mb-6 flex items-baseline justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">Agenda</h1>
                <p class="mt-1 text-sm text-[color:var(--nx-muted)]">
                    Boards für alles, was du im Blick behalten willst. Auf jeder Detailseite legst du Dinge
                    über den Reiter „Agenda“ im Kontext-Panel drauf.
                </p>
            </div>
        </div>

        <form wire:submit="create" class="mb-6 flex items-center gap-2">
            <input type="text" wire:model="newName" maxlength="120" placeholder="Neue Agenda, z. B. „Jour fixe Vertrieb“"
                   class="min-w-0 flex-1 rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-1.5 text-sm text-[color:var(--nx-text)] placeholder:text-[color:var(--nx-faint)] outline-none focus:border-[color:var(--nx-accent)]">
            <x-nx-button type="submit" variant="primary">
                @svg('heroicon-o-plus', 'w-4 h-4') Anlegen
            </x-nx-button>
        </form>

        @if(empty($this->agendas))
            <x-nx-empty icon="heroicon-o-queue-list">
                Noch keine Agenda — leg oben die erste an.
            </x-nx-empty>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->agendas as $agenda)
                    <a href="{{ route('home.agenda.show', $agenda['id']) }}" wire:navigate wire:key="agenda-{{ $agenda['id'] }}"
                       class="group flex flex-col rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4 shadow-[var(--nx-shadow-card)] transition-shadow hover:shadow-[0_2px_8px_rgba(15,15,15,.08),0_1px_2px_rgba(15,15,15,.05)]">
                        <div class="flex items-start gap-2">
                            @svg('heroicon-o-view-columns', 'mt-0.5 h-4 w-4 shrink-0 text-[color:var(--nx-faint)]')
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $agenda['name'] }}</div>
                                @if($agenda['description'])
                                    <div class="mt-0.5 line-clamp-2 text-xs text-[color:var(--nx-muted)]">{{ $agenda['description'] }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-3 text-xs text-[color:var(--nx-muted)]">
                            <span class="inline-flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-[color:var(--nx-info)]"></span>
                                <span class="tabular-nums">{{ $agenda['open'] }}</span> offen
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-[color:var(--nx-success)]"></span>
                                <span class="tabular-nums">{{ $agenda['done'] }}</span> erledigt
                            </span>
                            @if($agenda['members'] > 1)
                                <span class="inline-flex items-center gap-1" title="Geteilt">
                                    @svg('heroicon-o-users', 'h-3.5 w-3.5 opacity-60')
                                    <span class="tabular-nums">{{ $agenda['members'] }}</span>
                                </span>
                            @endif
                            <span class="ml-auto text-[color:var(--nx-faint)]">{{ $agenda['updated'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
