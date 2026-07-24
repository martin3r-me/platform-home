<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Notizen – schnelle persönliche Notizen (Platzhalter).
 */
class Notizen extends Component
{
    public function render()
    {
        return view('home::livewire.notizen')->layout('platform::layouts.app');
    }
}
