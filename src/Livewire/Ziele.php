<?php

namespace Platform\Home\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Meine Ziele – Objectives, für die ich verantwortlich bin (Owner oder Manager).
 * Konsumiert den okr-Kontrakt PersonObjectiveSummary::forUser (kein direkter
 * Modellzugriff); fehlt okr, bleibt die Ansicht leer statt zu brechen.
 */
class Ziele extends Component
{
    public bool $available = true;

    /** @var array<int, array<string,mixed>> */
    public array $items = [];

    public int $total = 0;
    public int $mountains = 0;
    public ?string $avgLabel = null;

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

        $svcClass = \Platform\Okr\Services\PersonObjectiveSummary::class;
        if (!class_exists($svcClass)) {
            $this->available = false;
            return;
        }

        try {
            $data = resolve($svcClass)->forUser($userId);
        } catch (\Throwable $e) {
            $this->available = false;
            return;
        }

        $this->items     = $data['items'] ?? [];
        $this->total     = (int) ($data['total'] ?? 0);
        $this->mountains = (int) ($data['mountains'] ?? 0);
        $this->avgLabel  = isset($data['avg_score']) && $data['avg_score'] !== null
            ? round($data['avg_score'] * 100) . '%'
            : null;
    }

    public function render()
    {
        return view('home::livewire.ziele')->layout('platform::layouts.app');
    }
}
