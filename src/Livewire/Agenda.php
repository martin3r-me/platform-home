<?php

namespace Platform\Home\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Home\Services\AgendaService;

/**
 * Agenda – meine Agenden im Überblick. Jede Agenda ist ein Kanban-Board
 * (home.agenda.show); befüllt wird sie über den Agenda-Reiter im Kontext-Panel
 * jeder Detailseite oder direkt auf dem Board.
 */
class Agenda extends Component
{
    public string $newName = '';

    #[Computed]
    public function agendas(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $service = app(AgendaService::class);

        return $service->agendasFor($user->id, $service->teamIdFor($user))
            ->loadCount(['items as done_count' => fn ($q) => $q->where('is_done', true), 'slots', 'members'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'description' => $a->description,
                'open' => (int) $a->item_count,
                'done' => (int) $a->done_count,
                'slots' => (int) $a->slots_count,
                'members' => (int) $a->members_count,
                'role' => $a->members->first()?->role,
                'updated' => $a->updated_at?->diffForHumans(),
            ])
            ->all();
    }

    public function create()
    {
        $user = Auth::user();
        $teamId = app(AgendaService::class)->teamIdFor($user);
        $name = trim($this->newName);
        if (! $user || ! $teamId || $name === '') {
            return null;
        }

        $agenda = app(AgendaService::class)->create($teamId, $user->id, mb_substr($name, 0, 120));
        $this->newName = '';

        return $this->redirectRoute('home.agenda.show', $agenda->id, navigate: true);
    }

    public function render()
    {
        return view('home::livewire.agenda')->layout('platform::layouts.app');
    }
}
