<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Home – Modul-Sidebar. Wird vom Layout automatisch als Haupt-Sidebar
 * eingebunden (@livewire('home.sidebar'), siehe platform::layouts.app).
 */
class Sidebar extends Component
{
    public function render()
    {
        return view('home::livewire.sidebar');
    }
}
