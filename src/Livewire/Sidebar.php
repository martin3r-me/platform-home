<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Platform\Core\PlatformCore;
use Platform\Core\Models\Module;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Home – Modul-Sidebar. Wird vom Layout automatisch als Haupt-Sidebar
 * eingebunden (@livewire('home.sidebar'), siehe platform::layouts.app).
 *
 * Bietet neben der Home-Navigation zentral alle für den User im aktuellen Team
 * zugänglichen Module an (Home als Hub).
 */
class Sidebar extends Component
{
    /** [ ['key','title','icon','url'], … ] – zugängliche Module, sortiert. */
    public array $modules = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return;
        }

        $this->modules = $this->loadAccessibleModules($user, $user->currentTeamRelation);
    }

    protected function loadAccessibleModules($user, $baseTeam): array
    {
        if (!$baseTeam) {
            return [];
        }

        $registered = PlatformCore::getVisibleModules();
        if (empty($registered)) {
            return [];
        }

        $keys = array_values(array_filter(array_map(fn ($m) => $m['key'] ?? null, $registered)));
        $modelsByKey = Module::whereIn('key', $keys)->get()->keyBy('key');

        return collect($registered)
            ->filter(function ($m) use ($modelsByKey, $user, $baseTeam) {
                if (($m['key'] ?? null) === 'home') {
                    return false; // Home nicht auf sich selbst verlinken
                }
                $model = $modelsByKey->get($m['key'] ?? null);
                return $model && $model->hasAccess($user, $baseTeam);
            })
            ->sortBy(fn ($m) => $m['navigation']['order'] ?? 999)
            ->map(function ($m) {
                $route = $m['navigation']['route'] ?? null;
                $url = ($route && Route::has($route)) ? route($route) : ($m['url'] ?? '#');
                return [
                    'key'   => $m['key'] ?? '',
                    'title' => $m['title'] ?? $m['label'] ?? ucfirst($m['key'] ?? ''),
                    'icon'  => $m['navigation']['icon'] ?? ($m['icon'] ?? 'heroicon-o-cube'),
                    'url'   => $url,
                ];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        return view('home::livewire.sidebar');
    }
}
