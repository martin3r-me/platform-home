<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Meine Zeiten – gestempelte Zeiten (Platzhalter).
 * Später: vollständige Ansicht aller OrganizationTimeEntry des Users (Filter,
 * Verlauf, abgerechnet/offen). Das Dashboard zeigt bereits die letzten 7 Tage.
 */
class Zeiten extends Component
{
    public function render()
    {
        return view('home::livewire.zeiten')->layout('platform::layouts.app');
    }
}
