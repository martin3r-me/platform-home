<?php

namespace Platform\Home\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Meine Aufgaben – offene, mir zugewiesene To-dos aus planner (Frogs zuerst).
 * Konsumiert den planner-Kontrakt PersonTaskSummary::openForUser (kein direkter
 * Modellzugriff); fehlt planner, bleibt die Ansicht leer statt zu brechen.
 */
class Aufgaben extends Component
{
    public bool $available = true;

    /** @var array<int, array<string,mixed>> */
    public array $items = [];

    public int $total = 0;
    public int $overdue = 0;
    public int $frogs = 0;

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $svcClass = \Platform\Planner\Services\PersonTaskSummary::class;
        if (!class_exists($svcClass)) {
            $this->available = false;
            return;
        }

        try {
            $data = resolve($svcClass)->openForUser($userId);
        } catch (\Throwable $e) {
            $this->available = false;
            return;
        }

        $this->items   = $data['items'] ?? [];
        $this->total   = (int) ($data['total'] ?? 0);
        $this->overdue = (int) ($data['overdue'] ?? 0);
        $this->frogs   = (int) ($data['frogs'] ?? 0);
    }

    public function render()
    {
        return view('home::livewire.aufgaben')->layout('platform::layouts.app');
    }
}
