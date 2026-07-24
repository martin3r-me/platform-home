<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Agenda – Dinge zum Sortieren & Weglegen (Platzhalter).
 * Später: modulübergreifende Items (Tasks, Tickets, Erwähnungen) triagieren.
 */
class Agenda extends Component
{
    public function render()
    {
        return view('home::livewire.agenda')->layout('platform::layouts.app');
    }
}
