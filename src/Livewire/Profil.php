<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Profil / Einstellungen – persönliche Kontoeinstellungen (Platzhalter).
 */
class Profil extends Component
{
    public function render()
    {
        return view('home::livewire.profil')->layout('platform::layouts.app');
    }
}
