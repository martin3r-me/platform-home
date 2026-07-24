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
    /** [ ['key','label','modules'=>[['key','title','icon','url'],…]], … ] – nach Gruppe. */
    public array $moduleGroups = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return;
        }

        $modules = $this->loadAccessibleModules($user, $user->currentTeamRelation);
        $this->moduleGroups = $this->groupModules($modules);
    }

    /**
     * Gruppiert die flache Modul-Liste nach Modul-Gruppe (Labels/Reihenfolge aus
     * PlatformCore::getModuleGroups(); unbekannte Gruppen ans Ende).
     */
    protected function groupModules(array $modules): array
    {
        if (empty($modules)) {
            return [];
        }

        $defs = PlatformCore::getModuleGroups();

        $buckets = [];
        foreach ($modules as $mod) {
            $g = $mod['group'] ?: 'other';
            $buckets[$g][] = $mod;
        }

        $out = [];
        foreach ($buckets as $gkey => $mods) {
            $out[] = [
                'key'     => $gkey,
                'label'   => $defs[$gkey]['label'] ?? ucfirst($gkey),
                'order'   => $defs[$gkey]['order'] ?? 90,
                'modules' => $mods,
            ];
        }

        usort($out, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $out;
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
                    'group' => $m['group'] ?? 'other',
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
