<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Core\Models\Checkin;
use Platform\Core\Models\CheckinTodo;
use Illuminate\Support\Facades\Auth;

/**
 * Mein Tag – fokussierte Tagesansicht: Begrüßung, Streak, heutiger Check-in,
 * offene Todos. (Aus dem ursprünglichen Home-Dashboard hierher gezogen.)
 */
class MeinTag extends Component
{
    public string $firstName = '';
    public string $greeting = '';
    public int $streak = 0;

    /** Heutiger Check-in als Array (oder null). */
    public ?array $todayCheckin = null;

    /** Offene Todos: [['id' => int, 'title' => string], ...]. */
    public array $openTodos = [];

    public function mount(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->firstName = trim(explode(' ', (string) $user->name)[0] ?? '');
        $this->greeting = $this->greetingForHour((int) now()->format('G'));

        $this->loadDay();
    }

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
            ->limit(12)
            ->get()
            ->map(fn (CheckinTodo $t) => ['id' => $t->id, 'title' => $t->title])
            ->all();
    }

    public function toggleTodo(int $id): void
    {
        $todo = CheckinTodo::with('checkin')->find($id);

        if (!$todo || (int) $todo->checkin?->user_id !== (int) Auth::id()) {
            return;
        }

        $todo->update(['done' => !$todo->done]);
        $this->loadDay();
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
        return view('home::livewire.mein-tag')->layout('platform::layouts.app');
    }
}
