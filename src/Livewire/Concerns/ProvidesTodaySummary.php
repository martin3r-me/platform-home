<?php

namespace Platform\Home\Livewire\Concerns;

use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Platform\Core\Models\CheckinTodo;
use Illuminate\Support\Facades\Auth;

/**
 * Tages-Daten fürs Zuhause-Cockpit — füllt die Widgets, die je einem Person-Kontrakt
 * eines Fremdmoduls entsprechen (Ziel: zu jedem Kontrakt ein Widget; später ein
 * Katalog, aus dem der User abonniert). Jeder Loader ist self-guarded (class_exists
 * + try/catch): fehlt ein Modul, bleibt der Wert neutral und das Widget blendet aus
 * statt die Seite zu brechen. Daten kommen ausschließlich über die Kontrakte, nie
 * über die Models der Fremdmodule.
 *
 *   Kontrakt                                    → Widget
 *   Core InboxQuery                             → Eingang-Box (in der View)
 *   Core Checkin / CheckinTodo                  → Check-in (Kopf) + To-dos
 *   organization PersonTimeSummary              → Zuletzt gestempelt (+ 7-Tage)
 *   organization PersonActivityRegistry         → Auf einen Blick (Vital Signs)
 *   planner PersonTaskSummary                   → Offene Aufgaben
 *   okr PersonObjectiveSummary                  → Meine Ziele
 *   academy AcademyAssignmentService            → Pflichtkurse
 *   user-connectors PersonCalendarService       → Termine heute
 *
 * `loadTodaySummary()` lädt das Leichte (für Dashboard UND /eingang-Kopf);
 * `loadDashboardExtras()` die schwereren Dashboard-only-Kontrakte (okr/academy/
 * vital signs/zeit-detail). Public Props persistieren über Livewire-Updates.
 */
trait ProvidesTodaySummary
{
    // --- Kopf ---
    public string $firstName = '';
    public string $greeting = '';
    public string $dateLabel = '';

    // --- Check-in (Core) ---
    public int $streak = 0;
    public ?array $todayCheckin = null;
    /** Offene Check-in-To-dos: [['id','title'], …]. */
    public array $openTodos = [];

    // --- Termine heute (user-connectors) ---
    public bool $calendarAvailable = true;
    public int $todayEventCount = 0;
    public array $todayEvents = [];

    // --- Offene Aufgaben (planner) ---
    public bool $tasksAvailable = true;
    public int $taskTotal = 0;
    public int $taskOverdue = 0;
    public int $taskFrogs = 0;
    public array $taskItems = [];

    // --- Zeiterfassung (organization) ---
    public bool $timeAvailable = true;
    /** Letzter Zeiteintrag: ['context','note','minutes','hours_label','when','is_billed'] — oder null. */
    public ?array $lastStamp = null;
    public int $time7dMinutes = 0;
    public string $time7dLabel = '';
    /** 7-Tage-Serie für den Mini-Chart: [['label','minutes'], …]. */
    public array $timeByDay = [];
    public int $timeMaxMinutes = 0;

    // --- Ziele (okr) ---
    public bool $goalsAvailable = true;
    public array $goalItems = [];
    public int $goalTotal = 0;
    public ?string $goalAvgLabel = null;

    // --- Pflichtkurse (academy) ---
    public bool $coursesAvailable = true;
    public array $courseItems = [];
    public int $courseOpen = 0;
    public int $courseOverdue = 0;

    // --- Vital Signs (organization PersonActivityRegistry) ---
    public bool $orgAvailable = true;
    public bool $hasPerson = false;
    public array $sectionConfigs = [];
    public array $vitalSigns = [];

    /** Leichte Tages-Daten — für Dashboard UND /eingang-Kopf. */
    protected function loadTodaySummary(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->firstName = trim(explode(' ', (string) $user->name)[0] ?? '');
        $this->greeting = $this->greetingForHour((int) now()->format('G'));
        $this->dateLabel = now()->locale('de')->isoFormat('dddd, D. MMMM YYYY');

        $this->loadDay();

        if ($user->currentTeam?->id) {
            $this->loadTasks($user->id);
            $this->loadCalendarToday($user->id);
        }
    }

    /** Schwerere Dashboard-only-Kontrakte (nur auf `/`, nicht im /eingang-Kopf). */
    protected function loadDashboardExtras(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->loadLastStamp();
        $this->loadTodos();

        if ($user->currentTeam?->id) {
            $this->loadGoals($user->id);
            $this->loadCourses($user->id, $user->currentTeam->id);
            $this->loadVitalSigns($user->id, $user->currentTeam->id);
        }
    }

    /** Streak + heutiger Check-in. Refresh-Hook nach dem globalen Check-in-Modal. */
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

    /** Offene Check-in-To-dos (Core). */
    protected function loadTodos(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

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

    /** Ein offenes To-do abhaken (nur eigene) — Aktion des To-dos-Widgets. */
    public function toggleTodo(int $id): void
    {
        $todo = CheckinTodo::with('checkin')->find($id);

        if (!$todo || (int) $todo->checkin?->user_id !== (int) Auth::id()) {
            return;
        }

        $todo->update(['done' => !$todo->done]);
        $this->loadTodos();
    }

    /** Offene Aufgaben (planner) — Zähler + Top-Liste (Frogs zuerst). */
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
        $this->taskItems = array_slice($data['items'] ?? [], 0, 5);
    }

    /** Termine heute (user-connectors) — Zähler + kurze Liste. */
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
        $this->todayEvents = is_array($firstDay) ? array_slice($firstDay['entries'] ?? [], 0, 5) : [];
    }

    /**
     * Zuletzt gestempelt + 7-Tage-Serie (organization PersonTimeSummary). Kein
     * Live-Timer — den gibt es im Backend nicht; ehrlich der jüngste Eintrag.
     */
    protected function loadLastStamp(): void
    {
        $svcClass = \Platform\Organization\Services\PersonTimeSummary::class;
        if (!class_exists($svcClass)) {
            $this->timeAvailable = false;
            return;
        }

        try {
            $svc = resolve($svcClass);
            $range = $svc->range(Auth::id(), now()->subDays(30)->toDateString(), now()->toDateString());
            $week  = $svc->lastDays(Auth::id(), 7);
        } catch (\Throwable $e) {
            $this->timeAvailable = false;
            return;
        }

        $this->time7dMinutes = (int) ($week['total_minutes'] ?? 0);
        $this->time7dLabel = $this->time7dMinutes > 0 ? $this->fmtHours($this->time7dMinutes) : '';

        $days = $week['days'] ?? [];
        $this->timeMaxMinutes = max(array_map(fn ($d) => (int) ($d['minutes'] ?? 0), $days) ?: [0]);
        $this->timeByDay = array_map(fn ($d) => [
            'label'   => $this->weekdayShort($d['date'] ?? null),
            'minutes' => (int) ($d['minutes'] ?? 0),
        ], $days);

        $entry = ($range['entries'] ?? [])[0] ?? null;
        if (!is_array($entry)) {
            return;
        }

        $minutes = (int) ($entry['minutes'] ?? 0);
        $this->lastStamp = [
            'context'     => trim((string) ($entry['context'] ?? '')) ?: 'Zeiteintrag',
            'note'        => trim((string) ($entry['note'] ?? '')),
            'minutes'     => $minutes,
            'hours_label' => $this->fmtHours($minutes),
            'when'        => $this->relDate($entry['date'] ?? null),
            'is_billed'   => (bool) ($entry['is_billed'] ?? false),
        ];
    }

    /** Meine Ziele (okr) — Top-Liste + Durchschnitts-Score. */
    protected function loadGoals(int $userId): void
    {
        $svcClass = \Platform\Okr\Services\PersonObjectiveSummary::class;
        if (!class_exists($svcClass)) {
            $this->goalsAvailable = false;
            return;
        }

        try {
            $data = resolve($svcClass)->forUser($userId);
        } catch (\Throwable $e) {
            $this->goalsAvailable = false;
            return;
        }

        $this->goalItems = array_slice($data['items'] ?? [], 0, 4);
        $this->goalTotal = (int) ($data['total'] ?? 0);
        $this->goalAvgLabel = isset($data['avg_score']) && $data['avg_score'] !== null
            ? round($data['avg_score'] * 100) . '%'
            : null;
    }

    /** Pflichtkurse (academy) — offene Liste + Zähler. */
    protected function loadCourses(int $userId, int $teamId): void
    {
        $svcClass = \Platform\Academy\Services\AcademyAssignmentService::class;
        if (!class_exists($svcClass)) {
            $this->coursesAvailable = false;
            return;
        }

        try {
            $raw = resolve($svcClass)->mandatoryForUser($userId, $teamId);
        } catch (\Throwable $e) {
            $this->coursesAvailable = false;
            return;
        }

        $open = array_values(array_filter($raw, fn ($c) => empty($c['is_completed'])));
        $this->courseOpen = count($open);
        $this->courseOverdue = count(array_filter($raw, fn ($c) => !empty($c['is_overdue'])));
        $this->courseItems = array_slice($open, 0, 4);
    }

    /** Vital Signs (organization PersonActivityRegistry) — Knoten-KPIs. */
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

    /** Ziel-Detailseite für die KPIs eines Providers (Widget-Link). */
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

    /** Minuten → kurzes deutsches Label („45 min" / „2 h" / „7,5 h"). */
    protected function fmtHours(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 min';
        }
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $h = $minutes / 60;
        $label = rtrim(rtrim(number_format($h, 1, ',', '.'), '0'), ',');

        return $label . ' h';
    }

    /** Y-m-d → relatives Label (heute/gestern/Wochentag/Datum). */
    protected function relDate(?string $ymd): string
    {
        if (!$ymd) {
            return '';
        }
        try {
            $d = \Illuminate\Support\Carbon::parse($ymd)->startOfDay();
        } catch (\Throwable $e) {
            return '';
        }

        $diff = $d->diffInDays(now()->startOfDay(), false);

        return match (true) {
            $diff === 0 => 'heute',
            $diff === 1 => 'gestern',
            $diff > 1 && $diff < 7 => $d->locale('de')->isoFormat('dddd'),
            default => $d->locale('de')->isoFormat('D. MMM'),
        };
    }

    /** Y-m-d → kurzer Wochentag (Mo/Di/…). */
    protected function weekdayShort(?string $ymd): string
    {
        if (!$ymd) {
            return '';
        }
        try {
            return \Illuminate\Support\Carbon::parse($ymd)->locale('de')->isoFormat('dd');
        } catch (\Throwable $e) {
            return '';
        }
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
