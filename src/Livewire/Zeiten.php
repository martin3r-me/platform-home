<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Meine Zeiten – vollständige Ansicht der vom User gestempelten Zeiten.
 * Konsumiert den organization-Kontrakt PersonTimeSummary (kein direkter Modellzugriff).
 */
class Zeiten extends Component
{
    /** Zeitraum: '7' | '30' | 'month'. */
    public string $period = '7';

    public bool $available = true;

    /** [ ['date','label','minutes','hours','entries'=>[...]] ] – neueste zuerst. */
    public array $days = [];

    public string $total = '0h';
    public string $billed = '0h';
    public string $open = '0h';
    public string $amount = '0,00 €';
    public int $entryCount = 0;

    public function mount(): void
    {
        $this->load();
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['7', '30', 'month'], true) ? $period : '7';
        $this->load();
    }

    public function load(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $svcClass = \Platform\Organization\Services\PersonTimeSummary::class;
        if (!class_exists($svcClass)) {
            $this->available = false;
            return;
        }

        [$from, $to] = $this->rangeDates();
        $data = resolve($svcClass)->range($userId, $from, $to);

        // Nach Tag gruppieren (Einträge kommen bereits neueste zuerst).
        $byDay = [];
        foreach ($data['entries'] as $e) {
            $byDay[$e['date']][] = $e;
        }

        $days = [];
        foreach ($byDay as $date => $entries) {
            $dayMinutes = array_sum(array_map(fn ($e) => (int) $e['minutes'], $entries));
            $days[] = [
                'date'    => $date,
                'label'   => Carbon::parse($date)->locale('de')->isoFormat('dddd, D. MMMM'),
                'minutes' => $dayMinutes,
                'hours'   => $this->fmtHours($dayMinutes),
                'entries' => array_map(fn ($e) => [
                    'note'      => $e['note'],
                    'context'   => $e['context'],
                    'hours'     => $this->fmtHours((int) $e['minutes']),
                    'is_billed' => (bool) $e['is_billed'],
                ], $entries),
            ];
        }

        $this->days = $days;
        $this->entryCount = count($data['entries']);
        $this->total = $this->fmtHours((int) $data['total_minutes']);
        $this->billed = $this->fmtHours((int) $data['billed_minutes']);
        $this->open = $this->fmtHours((int) $data['open_minutes']);
        $this->amount = number_format(((int) $data['amount_cents']) / 100, 2, ',', '.') . ' €';
    }

    protected function rangeDates(): array
    {
        $to = now()->toDateString();
        $from = match ($this->period) {
            'month' => now()->startOfMonth()->toDateString(),
            '30'    => now()->subDays(29)->toDateString(),
            default => now()->subDays(6)->toDateString(),
        };
        return [$from, $to];
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

    public function render()
    {
        return view('home::livewire.zeiten')->layout('platform::layouts.app');
    }
}
