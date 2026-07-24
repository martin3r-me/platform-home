<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Checkin – täglicher Check-in inkl. Verlauf & Trends (Platzhalter).
 * Die tägliche Eingabe erfolgt aktuell über das globale Check-in-Modal.
 */
class Checkin extends Component
{
    public function render()
    {
        return view('home::livewire.checkin')->layout('platform::layouts.app');
    }
}
