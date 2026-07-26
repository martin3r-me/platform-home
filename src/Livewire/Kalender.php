<?php

namespace Platform\Home\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Mein Kalender – synchronisierte Termine des Users (kommende zuerst, nach Tag).
 * Konsumiert den user-connectors-Kontrakt PersonCalendarService::agenda (kein
 * direkter Modellzugriff); fehlt der Connector, bleibt die Ansicht leer.
 */
class Kalender extends Component
{
    public bool $available = true;

    /** Vorschau-Fenster in Tagen. */
    public int $daysAhead = 14;

    /** @var array<int, array<string,mixed>> nach Tag gruppierte Termine. */
    public array $days = [];

    public int $count = 0;
    public int $todayCount = 0;

    public function mount(): void
    {
        $this->load();
    }

    public function setRange(int $daysAhead): void
    {
        $this->daysAhead = in_array($daysAhead, [7, 14, 30], true) ? $daysAhead : 14;
        $this->load();
    }

    public function load(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $svcClass = \Platform\UserConnectors\Services\PersonCalendarService::class;
        if (!class_exists($svcClass)) {
            $this->available = false;
            return;
        }

        try {
            $data = resolve($svcClass)->agenda($userId, $this->daysAhead);
        } catch (\Throwable $e) {
            $this->available = false;
            return;
        }

        $this->days       = $data['days'] ?? [];
        $this->count      = (int) ($data['count'] ?? 0);
        $this->todayCount = (int) ($data['today_count'] ?? 0);
    }

    public function render()
    {
        return view('home::livewire.kalender')->layout('platform::layouts.app');
    }
}
