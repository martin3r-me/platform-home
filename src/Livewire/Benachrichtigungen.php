<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Benachrichtigungen – persönliche Inbox / Erwähnungen (Platzhalter).
 */
class Benachrichtigungen extends Component
{
    public function render()
    {
        return view('home::livewire.benachrichtigungen')->layout('platform::layouts.app');
    }
}
