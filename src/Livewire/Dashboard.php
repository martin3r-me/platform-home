<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
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

        $this->sectionConfigs = $registry->allSectionConfigs();
        $this->vitalSigns = $registry->allVitalSigns($user->id, $teamId);
        $this->responsibilities = $registry->allResponsibilities($user->id, $teamId, 5);
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
        $teClass = \Platform\Organization\Models\OrganizationTimeEntry::class;
        if (!class_exists($teClass)) {
            return;
        }

        $start = now()->subDays(6)->startOfDay();

        // 7 Tage vorinitialisieren (ältester zuerst).
        $byDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $byDay[$d->toDateString()] = [
                'label'   => $d->locale('de')->isoFormat('dd'),
                'minutes' => 0,
            ];
        }

        $rows = $teClass::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), now()->toDateString()])
            ->get(['work_date', 'minutes', 'is_billed']);

        $total = 0;
        $billed = 0;
        foreach ($rows as $r) {
            $key = \Illuminate\Support\Carbon::parse($r->work_date)->toDateString();
            $m = (int) ($r->minutes ?? 0);
            if (isset($byDay[$key])) {
                $byDay[$key]['minutes'] += $m;
            }
            $total += $m;
            if ($r->is_billed) {
                $billed += $m;
            }
        }

        $this->timeMaxMinutes = max(array_map(fn ($d) => $d['minutes'], $byDay) ?: [0]);
        $this->timeByDay = array_map(fn ($d) => [
            'label'   => $d['label'],
            'minutes' => $d['minutes'],
            'hours'   => $this->fmtHours($d['minutes']),
        ], array_values($byDay));

        $this->timeTotal = $this->fmtHours($total);
        $this->timeBilled = $this->fmtHours($billed);
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
