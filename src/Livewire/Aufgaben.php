<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Meine Aufgaben – committete To-dos aus planner/fokusplan (Platzhalter).
 */
class Aufgaben extends Component
{
    public function render()
    {
        return view('home::livewire.aufgaben')->layout('platform::layouts.app');
    }
}
