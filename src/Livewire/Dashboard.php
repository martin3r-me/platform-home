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
