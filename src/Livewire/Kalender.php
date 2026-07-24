<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Kalender – aggregiert zeitbezogene Dinge modulübergreifend (Platzhalter).
 */
class Kalender extends Component
{
    public function render()
    {
        return view('home::livewire.kalender')->layout('platform::layouts.app');
    }
}
