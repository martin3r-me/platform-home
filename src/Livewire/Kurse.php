<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Meine Kurse – Pflicht-Academy-Kurse des Users mit Status + Fortschritt.
 * Konsumiert den academy-Kontrakt AcademyAssignmentService::mandatoryForUser
 * (kein direkter Modellzugriff).
 */
class Kurse extends Component
{
    public bool $available = true;

    /** angereicherte Kurs-Liste. */
    public array $courses = [];

    public int $total = 0;
    public int $done = 0;
    public int $open = 0;
    public int $overdue = 0;

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return;
        }

        $svcClass = \Platform\Academy\Services\AcademyAssignmentService::class;
        if (!class_exists($svcClass)) {
            $this->available = false;
            return;
        }

        $raw = resolve($svcClass)->mandatoryForUser($user->id, $user->currentTeam->id);

        $this->courses = array_map(function ($c) {
            $c['status_label']   = $c['is_completed'] ? 'erledigt' : ($c['is_overdue'] ? 'überfällig' : 'offen');
            $c['status_variant'] = $c['is_completed'] ? 'success' : ($c['is_overdue'] ? 'danger' : 'warning');
            $c['due_label']      = $c['is_overdue']
                ? 'überfällig'
                : ($c['due_at'] ? 'fällig ' . Carbon::parse($c['due_at'])->format('d.m.Y') : null);
            return $c;
        }, $raw);

        $this->total = count($this->courses);
        $this->done = count(array_filter($this->courses, fn ($c) => $c['is_completed']));
        $this->overdue = count(array_filter($this->courses, fn ($c) => $c['is_overdue']));
        $this->open = $this->total - $this->done;
    }

    public function render()
    {
        return view('home::livewire.kurse')->layout('platform::layouts.app');
    }
}
