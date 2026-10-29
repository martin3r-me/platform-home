<?php

namespace Platform\Home\Livewire\Concerns;

use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Platform\Core\Models\CheckinTodo;
use Illuminate\Support\Facades\Auth;

/**
 * „Heute"-Schiene des Cockpits — die orientierende Kontext-Spalte links neben dem
 * Strom (Begrüßung, Check-in, Termine heute, Fokus, Knoten-KPIs). Aus dem früheren
 * Dashboard gehoben: dessen tiefe Listen (7-Tage-Zeiten, Ziele, Aufgaben) leben auf
 * ihren eigenen Seiten; hier nur Kennzahlen + heutiger Fokus. Jeder Loader ist
 * self-guarded (class_exists + try/catch) — fehlt ein Modul, bleibt der Block leer
 * statt die Seite zu brechen.
 *
 * Nutzung: einmal in mount() $this->loadTodayRail() aufrufen (nur im Stream-Modus);
 * die public Props persistieren über Livewire-Updates, KPIs werden also nicht bei
 * jedem Filterklick neu geladen. Refresh nach Check-in via #[On('checkin-saved')].
 */
trait ProvidesTodayRail
{
    // --- Kopf ---
    public string $firstName = '';
    public string $greeting = '';
    public string $dateLabel = '';
    public ?string $avatar = null;

    // --- Person-Knoten ---
    public bool $hasPerson = false;
    public bool $orgAvailable = true;
    public ?string $personName = null;

    // --- Täglicher Check-in (Core) ---
    public int $streak = 0;
    public ?array $todayCheckin = null;
    /** Offene Check-in-To-dos: [['id'=>int,'title'=>string], …]. */
    public array $openTodos = [];

    // --- Termine heute (user-connectors) ---
    public bool $calendarAvailable = true;
    public array $todayEvents = [];
    public int $todayEventCount = 0;

    // --- Fokus: Aufgaben (planner) — nur Zähler fürs Cockpit ---
    public bool $tasksAvailable = true;
    public int $taskTotal = 0;
    public int $taskOverdue = 0;
    public int $taskFrogs = 0;

    // --- Knoten-KPIs (Vital Signs) ---
    /** [sectionKey => ['label','icon','description']] */
    public array $sectionConfigs = [];
    /** [sectionKey => [ ['key','label','value','variant'], … ]] */
    public array $vitalSigns = [];

    // --- Pflichtkurse (academy) — nur der überfällig-Zähler ---
    public int $courseOverdue = 0;

    /**
     * Die Schiene füllen. Einmal in mount() im Stream-Modus aufrufen.
     */
    protected function loadTodayRail(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->firstName = trim(explode(' ', (string) $user->name)[0] ?? '');
        $this->greeting = $this->greetingForHour((int) now()->format('G'));
        $this->dateLabel = now()->locale('de')->isoFormat('dddd, D. MMMM YYYY');
        $this->avatar = $user->avatar ?? null;

        $this->loadDay();

        $teamId = $user->currentTeam?->id;
        if (!$teamId) {
            return;
        }

        $this->loadTasks($user->id);
        $this->loadCalendarToday($user->id);
        $this->loadCourses($user->id, $teamId);
        $this->loadVitalSigns($user->id, $teamId);
    }

    /**
     * Heutiger Check-in + Streak + offene To-dos (Core). Auch Refresh-Hook nach
     * dem Speichern im globalen Check-in-Modal.
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

        $this->openTodos = CheckinTodo::whereHas(
                'checkin',
                fn ($q) => $q->where('user_id', $userId)
            )
            ->where('done', false)
            ->orderByDesc('checkin_id')
            ->limit(8)
            ->get()
            ->map(fn (CheckinTodo $t) => ['id' => $t->id, 'title' => $t->title])
            ->all();
    }

    /**
     * Ein offenes Check-in-To-do abhaken (nur eigene).
     */
    public function toggleTodo(int $id): void
    {
        $todo = CheckinTodo::with('checkin')->find($id);

        if (!$todo || (int) $todo->checkin?->user_id !== (int) Auth::id()) {
            return;
        }

        $todo->update(['done' => !$todo->done]);
        $this->loadDay();
    }

    /**
     * Meine offenen Aufgaben (planner) — nur Zähler (Frogs/überfällig) fürs Cockpit.
     */
    protected function loadTasks(int $userId): void
    {
        $svcClass = \Platform\Planner\Services\PersonTaskSummary::class;
        if (!class_exists($svcClass)) {
            $this->tasksAvailable = false;
            return;
        }

        try {
            $data = resolve($svcClass)->openForUser($userId);
        } catch (\Throwable $e) {
            $this->tasksAvailable = false;
            return;
        }

        $this->taskTotal = (int) ($data['total'] ?? 0);
        $this->taskOverdue = (int) ($data['overdue'] ?? 0);
        $this->taskFrogs = (int) ($data['frogs'] ?? 0);
    }

    /**
     * Termine von heute (user-connectors) — 1-Tages-Fenster.
     */
    protected function loadCalendarToday(int $userId): void
    {
        $svcClass = \Platform\UserConnectors\Services\PersonCalendarService::class;
        if (!class_exists($svcClass)) {
            $this->calendarAvailable = false;
            return;
        }

        try {
            $data = resolve($svcClass)->agenda($userId, 1);
        } catch (\Throwable $e) {
            $this->calendarAvailable = false;
            return;
        }

        $this->todayEventCount = (int) ($data['today_count'] ?? 0);
        $firstDay = ($data['days'] ?? [])[0] ?? null;
        $this->todayEvents = is_array($firstDay) ? array_slice($firstDay['entries'] ?? [], 0, 4) : [];
    }

    /**
     * Pflichtkurse (academy) — nur der überfällig-Zähler.
     */
    protected function loadCourses(int $userId, int $teamId): void
    {
        $svcClass = \Platform\Academy\Services\AcademyAssignmentService::class;
        if (!class_exists($svcClass)) {
            return;
        }

        try {
            $raw = resolve($svcClass)->mandatoryForUser($userId, $teamId);
        } catch (\Throwable $e) {
            return;
        }

        $this->courseOverdue = count(array_filter($raw, fn ($c) => !empty($c['is_overdue'])));
    }

    /**
     * Knoten-KPIs (Vital Signs) des Person-Knotens über die PersonActivityRegistry.
     */
    protected function loadVitalSigns(int $userId, int $teamId): void
    {
        $entityClass = \Platform\Organization\Models\OrganizationEntity::class;
        $registryClass = \Platform\Organization\Services\PersonActivityRegistry::class;

        if (!class_exists($entityClass) || !class_exists($registryClass)) {
            $this->orgAvailable = false;
            return;
        }

        try {
            $person = $entityClass::forTeam($teamId)
                ->linkedToUser($userId)
                ->persons()
                ->first();
        } catch (\Throwable $e) {
            return;
        }

        if (!$person) {
            return;
        }

        $this->hasPerson = true;
        $this->personName = $person->name;

        try {
            $registry = resolve($registryClass);
            if (!$registry->hasProviders()) {
                return;
            }
            $this->sectionConfigs = $registry->allSectionConfigs();
            $this->vitalSigns = $registry->allVitalSigns($userId, $teamId);
        } catch (\Throwable $e) {
            // still — KPIs bleiben leer
        }
    }

    /**
     * Ziel-Detailseite für die KPIs eines Providers (Orientierung → Tiefe).
     */
    public function kpiHref(string $sectionKey): ?string
    {
        $map = [
            'academy'  => 'home.kurse',
            'planner'  => 'planner.my-tasks',
            'helpdesk' => 'helpdesk.dashboard',
        ];

        $route = $map[$sectionKey] ?? null;

        return $route && \Illuminate\Support\Facades\Route::has($route)
            ? route($route)
            : null;
    }

    protected function greetingForHour(int $hour): string
    {
        return match (true) {
            $hour < 11 => 'Guten Morgen',
            $hour < 18 => 'Guten Tag',
            default    => 'Guten Abend',
        };
    }
}
