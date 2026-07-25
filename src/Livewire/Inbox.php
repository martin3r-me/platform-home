<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

/**
 * Eingang – die persönliche Inbox als Herzstück von HOME (3-Pane, nx-Stil).
 * Aktuell Layout-Skelett mit Platzhaltern:
 *   - links (Sidebar):  Kanäle & Filter
 *   - rechts (Activity): Item-Liste
 *   - Mitte (Content):   Reading-Pane (Header · KI-Summary · Body · Aktionen)
 *
 * Inhalte folgen über den Inbox-Kontrakt (InboxItem, status/snooze, deliver()).
 */
class Inbox extends Component
{
    public function render()
    {
        return view('home::livewire.inbox')->layout('platform::layouts.app');
    }
}
