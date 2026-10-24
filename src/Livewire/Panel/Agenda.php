<?php

namespace Platform\Home\Livewire\Panel;

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Core\Livewire\Terminal\Concerns\WithTerminalContext;
use Platform\Home\Services\AgendaService;

/**
 * Agenda-Reiter im Kontext-Panel: den aktuellen Datensatz mit einem Klick auf
 * eine (oder mehrere) meiner Agenden legen bzw. wieder herunternehmen. Das Board
 * selbst lebt in home (home.agenda.show).
 */
class Agenda extends Component
{
    use WithTerminalContext;

    public string $newAgendaName = '';

    #[Computed]
    public function agendas(): array
    {
        $userId = auth()->id();
        if (! $userId || ! $this->hasContext()) {
            return [];
        }

        $service = app(AgendaService::class);
        $on = array_flip($service->agendaIdsContaining($this->contextType, $this->contextId, $userId, $this->agendaTeamId()));
        $hasBoard = Route::has('home.agenda.show');

        return $service->agendasFor($userId, $this->agendaTeamId())
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'count' => (int) $a->item_count,
                'on' => isset($on[$a->id]),
                'url' => $hasBoard ? route('home.agenda.show', $a->id) : null,
            ])
            ->all();
    }

    public function toggle(int $agendaId): void
    {
        $userId = auth()->id();
        if (! $userId || ! $this->hasContext()) {
            return;
        }

        $service = app(AgendaService::class);
        $agenda = $service->findFor($agendaId, $userId, $this->agendaTeamId());
        if (! $agenda) {
            return;
        }

        if (in_array($agenda->id, $service->agendaIdsContaining($this->contextType, $this->contextId, $userId, $this->agendaTeamId()), true)) {
            $service->detach($agenda, $this->contextType, $this->contextId);
        } else {
            $service->attach($agenda, $this->contextType, $this->contextId, $this->contextSubject);
        }

        $this->changed();
    }

    /** Neue Agenda anlegen und den Datensatz gleich drauflegen. */
    public function createAndAttach(): void
    {
        $userId = auth()->id();
        $teamId = $this->agendaTeamId();
        $name = trim($this->newAgendaName);
        if (! $userId || ! $teamId || $name === '' || ! $this->hasContext()) {
            return;
        }

        $service = app(AgendaService::class);
        $agenda = $service->create($teamId, $userId, mb_substr($name, 0, 120));
        $service->attach($agenda, $this->contextType, $this->contextId, $this->contextSubject);

        $this->newAgendaName = '';
        $this->changed();
    }

    protected function agendaTeamId(): ?int
    {
        return app(AgendaService::class)->teamIdFor(auth()->user());
    }

    protected function changed(): void
    {
        unset($this->agendas);
        $this->dispatch('context-panel-badges');
    }

    public function render()
    {
        return view('home::livewire.panel.agenda', [
            'indexUrl' => Route::has('home.agenda') ? route('home.agenda') : null,
        ]);
    }
}
