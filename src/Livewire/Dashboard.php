<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Platform\Core\Models\CheckinTodo;
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

    /** Lesbares Datum (dddd, D. MMMM YYYY) für den Kopf. */
    public string $dateLabel = '';

    // --- Getrackte Zeiten (letzte 7 Tage) ---
    public bool $hasTime = false;
    /** [ ['label','minutes','hours'], … ] – 7 Einträge, ältester zuerst. */
    public array $timeByDay = [];
    public int $timeMaxMinutes = 0;
    public string $timeTotal = '0h';
    public string $timeBilled = '0h';

    // --- Heute: offene Check-in-To-dos (aus „Mein Tag" integriert) ---
    /** Offene Todos: [['id'=>int,'title'=>string], …]. */
    public array $openTodos = [];

    // --- Heute: Termine (user-connectors) ---
    public bool $calendarAvailable = true;
    /** Termine von heute: [['subject','time_label','location','is_now','is_online','is_past'], …]. */
    public array $todayEvents = [];
    public int $todayEventCount = 0;

    // --- Meine Aufgaben (planner, Frogs zuerst) ---
    public bool $tasksAvailable = true;
    /** Top-Aufgaben (max. 5) für die Fokus-Liste. */
    public array $taskItems = [];
    public int $taskTotal = 0;
    public int $taskOverdue = 0;
    public int $taskFrogs = 0;

    // --- Meine Ziele (okr) ---
    public bool $goalsAvailable = true;
    /** Top-Ziele (max. 3): [['title','score','score_label','score_variant','is_mountain','url'], …]. */
    public array $goalItems = [];
    public int $goalTotal = 0;
    public ?string $goalAvgLabel = null;

    // --- Eingang (Core InboxQuery) ---
    public bool $eingangAvailable = true;
    public int $eingangOpen = 0;
    public int $eingangUnread = 0;
    /** Kurze Vorschau der obersten Eingänge: [['label','sub'], …] (max. 3). */
    public array $eingangTeaser = [];

    // --- Pflichtkurse (academy) ---
    public int $courseOpen = 0;
    public int $courseOverdue = 0;

    public function mount(): void
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

        // Cockpit-Daten — jeder Loader ist self-guarded (class_exists + try/catch):
        // fehlt ein Modul, bleibt der Block leer statt die Seite zu brechen.
        $this->loadTime($user->id);
        $this->loadTasks($user->id);
        $this->loadGoals($user->id);
        $this->loadCalendarToday($user->id);
        $this->loadCourses($user->id, $teamId);
        $this->loadEingang($user->id, $teamId);

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
     * Ein offenes Check-in-To-do abhaken (nur eigene). Aus „Mein Tag" integriert.
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
     * Ziel-Detailseite für die KPIs eines Providers (Orientierung → Tiefe).
     * Guarded: unbekannte/fehlende Routen → null (KPI dann nicht klickbar).
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

    /**
     * Meine offenen Aufgaben (planner, Frogs/überfällig zuerst) — Top 5 für die
     * Fokus-Liste. Kontrakt PersonTaskSummary::openForUser; fehlt planner, leer.
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

        $this->taskItems = array_slice($data['items'] ?? [], 0, 5);
        $this->taskTotal = (int) ($data['total'] ?? 0);
        $this->taskOverdue = (int) ($data['overdue'] ?? 0);
        $this->taskFrogs = (int) ($data['frogs'] ?? 0);
    }

    /**
     * Meine Ziele (okr) — Top 3 + Durchschnitts-Score. Kontrakt
     * PersonObjectiveSummary::forUser; fehlt okr, leer.
     */
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

        $this->goalItems = array_slice($data['items'] ?? [], 0, 3);
        $this->goalTotal = (int) ($data['total'] ?? 0);
        $this->goalAvgLabel = isset($data['avg_score']) && $data['avg_score'] !== null
            ? round($data['avg_score'] * 100) . '%'
            : null;
    }

    /**
     * Termine von heute (user-connectors) — Kontrakt PersonCalendarService::agenda
     * im 1-Tages-Fenster; fehlt der Connector, leer.
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
        // Erster zurückgegebener Tag = heute (agenda liefert kommende zuerst).
        $firstDay = ($data['days'] ?? [])[0] ?? null;
        $this->todayEvents = is_array($firstDay) ? array_slice($firstDay['entries'] ?? [], 0, 6) : [];
    }

    /**
     * Pflichtkurse (academy) — nur die Zähler offen/überfällig fürs Cockpit.
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

        $this->courseOpen = count(array_filter($raw, fn ($c) => empty($c['is_completed'])));
        $this->courseOverdue = count(array_filter($raw, fn ($c) => !empty($c['is_overdue'])));
    }

    /**
     * Eingang-Kurzstand über die EINE Core-InboxQuery (offen/ungelesen + Teaser).
     * home kennt die Quell-Module nicht — nur den konsolidierten Stream.
     */
    protected function loadEingang(int $userId, int $teamId): void
    {
        $contract = \Platform\Core\Inbox\Contracts\InboxQuery::class;
        if (!interface_exists($contract)) {
            $this->eingangAvailable = false;
            return;
        }

        try {
            $rows = app($contract)->listStreamForUser($userId, $teamId, 25);
        } catch (\Throwable $e) {
            $this->eingangAvailable = false;
            return;
        }

        $this->eingangOpen = count($rows);
        $this->eingangUnread = count(array_filter($rows, fn ($r) => !empty($r['unread'])));

        $this->eingangTeaser = array_map(function ($r) {
            $label = $r['subject'] ?? $r['sender'] ?? $r['title'] ?? 'Eingang';
            $sub = $r['when'] ?? $r['time_short'] ?? $r['preview'] ?? ($r['channel'] ?? '');
            return [
                'label' => \Illuminate\Support\Str::limit((string) $label, 48),
                'sub' => \Illuminate\Support\Str::limit((string) $sub, 40),
            ];
        }, array_slice($rows, 0, 3));
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
