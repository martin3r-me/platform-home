<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Mein Tag – fokussierte Tagesansicht (Platzhalter).
 * Später: Tagesziel, Check-in-Status, offene Todos, Streak.
 */
class MeinTag extends Component
{
    public function render()
    {
        return view('home::livewire.mein-tag')->layout('platform::layouts.app');
    }
}
