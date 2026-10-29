<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Home – Modul-Sidebar. Wird vom Layout automatisch als Haupt-Sidebar eingebunden
 * (@livewire('home.sidebar'), siehe platform::layouts.app).
 *
 * Reine PERSÖNLICHE Navigation (Zuhause/Eingang + „Meins"). KEIN Modul-Launcher
 * mehr: zwischen Modulen wird oben in der Topbar gewechselt (core.module-flyout +
 * Favoriten + „Alle Module"). Darum lädt diese Komponente nichts — keine Module-
 * Queries pro Seitenaufruf.
 */
class Sidebar extends Component
{
    public function render()
    {
        return view('home::livewire.sidebar');
    }
}
