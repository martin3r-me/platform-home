<?php

/**
 * Home Service Provider
 *
 * Das persönliche Zuhause-Modul der Platform — die Ich-Sicht als Spiegel von
 * `organization` (Org-Sicht). Registriert das Modul, Routes, Views und
 * Livewire-Komponenten nach dem Standard-Muster (siehe module-template / HCM / Planner).
 *
 * @see Platform\Core\PlatformCore für Modul-Registrierung
 * @see Platform\Core\Routing\ModuleRouter für Route-Registrierung
 */

namespace Platform\Home;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HomeServiceProvider extends ServiceProvider
{
    /**
     * Config laden (Laravel Best Practice: in register(), nicht boot()).
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/home.php', 'home');
    }

    /**
     * Modul registrieren, Routes/Views/Livewire verdrahten.
     */
    public function boot(): void
    {
        // SCHRITT 1: Modul bei PlatformCore registrieren
        if (
            config()->has('home.routing') &&
            config()->has('home.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'home',
                'title'      => 'Home',
                'routing'    => config('home.routing'),
                'guard'      => config('home.guard'),
                'navigation' => config('home.navigation'),
            ]);
        }

        // SCHRITT 2: Routes laden (nur wenn Modul registriert)
        if (PlatformCore::getModule('home')) {
            ModuleRouter::group('home', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // SCHRITT 3: Migrationen
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // SCHRITT 4: Config publizierbar machen
        $this->publishes([
            __DIR__.'/../config/home.php' => config_path('home.php'),
        ], 'config');

        // SCHRITT 5: Views unter dem Namespace 'home'
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'home');

        // SCHRITT 6: Livewire-Komponenten automatisch registrieren
        $this->registerLivewireComponents();
    }

    /**
     * Registriert alle Livewire-Komponenten aus src/Livewire/ rekursiv.
     *
     * NAMING:
     * - Datei:     src/Livewire/Dashboard.php
     * - Namespace: Platform\Home\Livewire\Dashboard
     * - Alias:     home.dashboard
     */
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Home\\Livewire';
        $prefix = 'home';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
