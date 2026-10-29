<?php

namespace Platform\Home\Livewire\Concerns;

use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Illuminate\Support\Facades\Auth;

/**
 * Tages-Daten fürs Zuhause-Cockpit — die glanceable Kennzahlen + die kleinen
 * Listen, die die Dashboard-Karten füllen (Begrüßung, Streak, Check-in, zuletzt
 * gestempelt, offene Aufgaben, Termine heute). Jeder Loader ist self-guarded
 * (class_exists + try/catch): fehlt ein Modul, bleibt der Wert neutral statt die
 * Seite zu brechen. Daten kommen ausschließlich über die Person-Kontrakte der
 * Fremdmodule — nie über deren Models.
 *
 * Nutzung: einmal in mount() $this->loadTodaySummary() (nur wenn kein Item offen);
 * die public Props persistieren über Livewire-Updates (kein Reload bei Filter-/
 * Triage-Klick). Refresh nach Check-in via #[On('checkin-saved')].
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

    // --- Termine heute (user-connectors) ---
    public bool $calendarAvailable = true;
    public int $todayEventCount = 0;
    /** Kurze Liste der heutigen Termine fürs Dashboard: [['time_label','subject','is_now','is_past','is_online','location'], …]. */
    public array $todayEvents = [];

    // --- Offene Aufgaben (planner) ---
    public bool $tasksAvailable = true;
    public int $taskTotal = 0;
    public int $taskOverdue = 0;
    public int $taskFrogs = 0;
    /** Top-Aufgaben fürs Dashboard (max. 5), Frogs zuerst. */
    public array $taskItems = [];

    // --- Zeiterfassung: zuletzt gestempelt (organization) ---
    public bool $timeAvailable = true;
    /** Letzter Zeiteintrag: ['context','note','minutes','hours_label','when','is_billed'] — oder null. */
    public ?array $lastStamp = null;
    /** Summe der letzten 7 Tage (Minuten) + vorformatiertes Label. */
    public int $time7dMinutes = 0;
    public string $time7dLabel = '';

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
     * „Was haben wir zuletzt gestempelt?" — der jüngste Zeiteintrag + 7-Tage-Summe
     * über den user-scoped Kontrakt PersonTimeSummary::range (entries newest-first).
     * Nur fürs Dashboard laden (eine zusätzliche Query) — der volle Eingang braucht
     * es nicht. Kein Live-Timer: den gibt es im Backend nicht.
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

        $today = now()->startOfDay();
        $diff = $d->diffInDays($today, false);

        return match (true) {
            $diff === 0 => 'heute',
            $diff === 1 => 'gestern',
            $diff > 1 && $diff < 7 => $d->locale('de')->isoFormat('dddd'),
            default => $d->locale('de')->isoFormat('D. MMM'),
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
}
