<?php

namespace Platform\Home\Livewire\Concerns;

use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Illuminate\Support\Facades\Auth;

/**
 * Tages-Glance für den Cockpit-Kopf — die wenigen glanceable Kennzahlen, die
 * über dem Strom stehen (Begrüßung, Streak, Check-in-Status, Termine heute,
 * Fokus/Frogs). KEINE Listen, keine KPIs — die leben auf ihren eigenen Seiten;
 * hier nur Zahlen + Verlinkung. Jeder Loader ist self-guarded (class_exists +
 * try/catch): fehlt ein Modul, bleibt der Wert neutral statt die Seite zu brechen.
 *
 * Nutzung: einmal in mount() $this->loadTodaySummary() (nur im Stream-Modus); die
 * public Props persistieren über Livewire-Updates (kein Reload bei Filterklick).
 * Refresh nach Check-in via #[On('checkin-saved')].
 */
trait ProvidesTodaySummary
{
    public string $firstName = '';
    public string $greeting = '';
    public string $dateLabel = '';

    public int $streak = 0;
    public ?array $todayCheckin = null;

    public bool $calendarAvailable = true;
    public int $todayEventCount = 0;

    public bool $tasksAvailable = true;
    public int $taskTotal = 0;
    public int $taskOverdue = 0;
    public int $taskFrogs = 0;

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

    /** Offene Aufgaben (planner) — nur Zähler (Frogs/überfällig). */
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

    /** Termine heute (user-connectors) — nur der Zähler. */
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
