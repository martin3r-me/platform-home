<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Meine Ziele – relevante OKRs aus der Zeitplanung (Platzhalter).
 */
class Ziele extends Component
{
    public function render()
    {
        return view('home::livewire.ziele')->layout('platform::layouts.app');
    }
}
