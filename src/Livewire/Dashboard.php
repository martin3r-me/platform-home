<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard – das persönliche Cockpit, verdrahtet gegen den Person-Knoten
 * im organization-Modul (OrganizationEntity.linked_user_id).
 *
 * Datenquelle: PersonActivityRegistry (Module registrieren PersonActivityProvider) —
 * Vital Signs + Responsibilities des eingeloggten Users im aktuellen Team.
 * Home ist die persönliche Sicht auf den Knoten; es aggregiert nichts selbst.
 */
class Dashboard extends Component
{
    public string $firstName = '';
    public string $greeting = '';

    /** Avatar-URL des Users (für die Kontext-Sidebar). */
    public ?string $avatar = null;

    // --- Täglicher Check-in (Core) ---
    public int $streak = 0;
    /** Heutiger Check-in als Array (oder null). */
    public ?array $todayCheckin = null;

    /** Ist ein Person-Knoten mit dem User verknüpft? */
    public bool $hasPerson = false;

    /** Ist das organization-Modul überhaupt vorhanden? */
    public bool $orgAvailable = true;

    /** Name des Person-Knotens. */
    public ?string $personName = null;

    /** [sectionKey => ['label','icon','description']] */
    public array $sectionConfigs = [];

    /** [sectionKey => [ ['key','label','value','variant'], ... ]] */
    public array $vitalSigns = [];

    /** [sectionKey => [ ['key','label','icon','total_count','items'=>[...]], ... ]] */
    public array $responsibilities = [];

    // --- Getrackte Zeiten (letzte 7 Tage) ---
    public bool $hasTime = false;
    /** [ ['label','minutes','hours'], … ] – 7 Einträge, ältester zuerst. */
    public array $timeByDay = [];
    public int $timeMaxMinutes = 0;
    public string $timeTotal = '0h';
    public string $timeBilled = '0h';

    public function mount(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->firstName = trim(explode(' ', (string) $user->name)[0] ?? '');
        $this->greeting = $this->greetingForHour((int) now()->format('G'));
        $this->avatar = $user->avatar ?? null;

        $this->loadDay();

        $teamId = $user->currentTeam?->id;
        if (!$teamId) {
            return;
        }

        $this->loadTime($user->id);

        $entityClass = \Platform\Organization\Models\OrganizationEntity::class;
        $registryClass = \Platform\Organization\Services\PersonActivityRegistry::class;

        if (!class_exists($entityClass) || !class_exists($registryClass)) {
            $this->orgAvailable = false;
            return;
        }

        // Person-Knoten des Users im aktuellen Team auflösen.
        $person = $entityClass::forTeam($teamId)
            ->linkedToUser($user->id)
            ->persons()
            ->first();

        if (!$person) {
            return;
        }

        $this->hasPerson = true;
        $this->personName = $person->name;

        // Aggregierte Daten vom Knoten ziehen (über die Provider-Registry).
        $registry = resolve($registryClass);
        if (!$registry->hasProviders()) {
            return;
        }

        // Dashboard = Orientierung: nur Kennzahlen (Vital Signs), keine Detail-Listen.
        // Die Zuständigkeits-Listen leben auf den eigenen Seiten (Meine Aufgaben/Kurse/…).
        $this->sectionConfigs = $registry->allSectionConfigs();
        $this->vitalSigns = $registry->allVitalSigns($user->id, $teamId);
    }

    /**
     * Heutiger Check-in + Streak (Core). Refresh nach dem Speichern im Modal.
     */
    #[On('checkin-saved')]
    public function loadDay(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $this->streak = Checkin::currentStreak($userId);

        $checkin = Checkin::where('user_id', $userId)
            ->where('date', now()->toDateString())
            ->first();
        $this->todayCheckin = $checkin?->toArray();
    }

    /**
     * Accent-Farbe (CSS) für eine Metrik-Variante.
     */
    public function accentFor(string $variant): ?string
    {
        return match ($variant) {
            'danger'  => 'var(--nx-danger)',
            'warning' => 'var(--nx-warning)',
            'success' => 'var(--nx-success)',
            default   => null,
        };
    }

    /**
     * Getrackte Zeiten der letzten 7 Tage (pro Tag + Summe/abgerechnet).
     * Quelle: OrganizationTimeEntry (user-scoped). Self-guard, falls organization fehlt.
     */
    protected function loadTime(int $userId): void
    {
        // Über den organization-Service (Kontrakt) — home hängt nicht am Zeit-Modell.
        $svcClass = \Platform\Organization\Services\PersonTimeSummary::class;
        if (!class_exists($svcClass)) {
            return;
        }

        $summary = resolve($svcClass)->lastDays($userId, 7);
        $days = $summary['days'] ?? [];

        $this->timeMaxMinutes = max(array_map(fn ($d) => (int) ($d['minutes'] ?? 0), $days) ?: [0]);
        $this->timeByDay = array_map(fn ($d) => [
            'label'   => \Illuminate\Support\Carbon::parse($d['date'])->locale('de')->isoFormat('dd'),
            'minutes' => (int) ($d['minutes'] ?? 0),
            'hours'   => $this->fmtHours((int) ($d['minutes'] ?? 0)),
        ], $days);

        $total = (int) ($summary['total_minutes'] ?? 0);
        $this->timeTotal = $this->fmtHours($total);
        $this->timeBilled = $this->fmtHours((int) ($summary['billed_minutes'] ?? 0));
        $this->hasTime = $total > 0;
    }

    protected function fmtHours(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0h';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $m ? "{$h}h {$m}m" : "{$h}h";
    }

    protected function greetingForHour(int $hour): string
    {
        return match (true) {
            $hour < 11 => 'Guten Morgen',
            $hour < 18 => 'Guten Tag',
            default    => 'Guten Abend',
        };
    }

    public function render()
    {
        return view('home::livewire.dashboard')->layout('platform::layouts.app');
    }
}
