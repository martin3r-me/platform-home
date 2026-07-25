<?php

namespace Platform\Home\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

/**
 * Eingang – die persönliche Inbox als Herzstück von HOME (3-Pane, nx-Stil).
 *
 * AKTUELL: klickbarer Prototyp mit Dummy-Daten + kanal-spezifischen Reading-Panes
 * (Mail=Thread, Meeting=Agenda, Aufnahme=Transcript, Aufgabe=Properties/DoD,
 * Teams=Chat, Anruf=Anrufdaten, System=Aktion). Zwei kombinierende Filter-Achsen:
 * Kanal (links) × Status (Toggle über der Liste).
 */
class Inbox extends Component
{
    public ?int $selectedId = 1;
    public string $channel = 'all';
    public string $status = 'all';

    public string $nodeQuery = '';

    public function selectItem(int $id): void
    {
        $this->selectedId = $id;
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Roter Faden: aus einem Eingang-Meeting ein VOLLWERTIGES Meeting machen.
     * Loose/guarded — das meetings-Modul muss nicht da sein; fehlt es, passiert nichts.
     * Danach docken Serien-Vorkommen über inbox_items.meeting_id an, und sobald das
     * Meeting an einem Org-Knoten hängt, werden die Zeiten automatisch getrackt.
     */
    public function promoteMeeting(): void
    {
        if (!$this->selectedId || $this->selectedId < 10000) {
            return; // nur echte Meeting-Items (eigener ID-Raum ab 10000)
        }

        $svc = \Platform\Meetings\Services\MeetingPromotionService::class;
        if (!class_exists($svc)) {
            return; // meetings-Modul nicht installiert → keine harte Kopplung
        }

        try {
            app($svc)->promoteInboxItem($this->selectedId - 10000);
        } catch (\Throwable $e) {
            // bleibt beim Kalender-Item, kein Fehler nach außen
        }
        // Re-render: detailForItem liefert nun meeting_id → UI kippt auf "Echtes Meeting".
    }

    /**
     * Item an einen Org-Knoten hängen — der Kern der Inbox: Dinge in die
     * Organisation hängen. Ist das Item schon promotet, hängt auch das Meeting an
     * denselben Knoten (Wissen fließt in den Puls; Zeit läuft separat übers Item).
     */
    public function attachNode(int $entityId): void
    {
        $item = $this->currentInboxItem();
        if (!$item) {
            return;
        }

        $svc = \Platform\Inbox\Services\InboxEntityLinkService::class;
        try {
            app($svc)->link($item, $entityId);

            $bridge = \Platform\Organization\Services\EntityDimensionBridge::class;
            if ($item->meeting_id && class_exists($bridge)) {
                $bridge::createLink($entityId, 'meeting', (int) $item->meeting_id);
            }
        } catch (\Throwable $e) {
            // Organization nicht verfügbar → still, Kontext bleibt leer.
        }

        $this->nodeQuery = '';
    }

    public function detachNode(int $entityId): void
    {
        $item = $this->currentInboxItem();
        if (!$item) {
            return;
        }
        try {
            app(\Platform\Inbox\Services\InboxEntityLinkService::class)->unlink($item, $entityId);
        } catch (\Throwable $e) {
            // still
        }
    }

    /** Der aktuell gewählte ECHTE Inbox-Datensatz (nur reale Items, ID ab 10000). */
    protected function currentInboxItem(): ?\Platform\Inbox\Models\InboxItem
    {
        if (!$this->selectedId || $this->selectedId < 10000) {
            return null;
        }
        if (!class_exists(\Platform\Inbox\Models\InboxItem::class)) {
            return null;
        }
        return \Platform\Inbox\Models\InboxItem::find($this->selectedId - 10000);
    }

    /** Knoten-Suchtreffer für den Picker (soft-coupled über den Inbox-Service). */
    protected function nodeResults(): array
    {
        $svc = \Platform\Inbox\Services\InboxEntityLinkService::class;
        if (!class_exists($svc) || trim($this->nodeQuery) === '') {
            return [];
        }
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return [];
        }
        try {
            return app($svc)->search($this->nodeQuery, $user->currentTeam->id, 8);
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function matches(array $item): bool
    {
        $channelOk = $this->channel === 'all' || ($item['channel'] ?? '') === $this->channel;

        $statusOk = match ($this->status) {
            'unread'  => (bool) ($item['unread'] ?? false),
            'snoozed' => ($item['status'] ?? '') === 'snoozed',
            'done'    => ($item['status'] ?? '') === 'done',
            default   => true,
        };

        return $channelOk && $statusOk;
    }

    /**
     * Kanal-Definitionen. Die Counts werden in render() aus der TATSÄCHLICHEN
     * (gemergten) Liste abgeleitet — nie hartkodiert, damit Zahl und Liste immer
     * zusammenpassen (Meeting ist echt verdrahtet, der Rest noch Dummy).
     */
    protected function channels(array $counts = []): array
    {
        $defs = [
            ['key' => 'all',       'label' => 'Alle',      'icon' => 'heroicon-o-inbox'],
            ['key' => 'mail',      'label' => 'E-Mail',    'icon' => 'heroicon-o-envelope'],
            ['key' => 'meeting',   'label' => 'Meeting',   'icon' => 'heroicon-o-calendar-days'],
            ['key' => 'task',      'label' => 'Aufgabe',   'icon' => 'heroicon-o-clipboard-document-check'],
            ['key' => 'message',   'label' => 'Teams',     'icon' => 'heroicon-o-chat-bubble-left'],
            ['key' => 'call',      'label' => 'Anruf',     'icon' => 'heroicon-o-phone'],
            ['key' => 'recording', 'label' => 'Aufnahme',  'icon' => 'heroicon-o-microphone'],
            ['key' => 'system',    'label' => 'System',    'icon' => 'heroicon-o-bell'],
        ];

        return array_map(fn ($c) => $c + ['count' => $counts[$c['key']] ?? 0], $defs);
    }

    protected function statuses(): array
    {
        return [
            ['key' => 'all',     'label' => 'Alle'],
            ['key' => 'unread',  'label' => 'Ungelesen'],
            ['key' => 'snoozed', 'label' => 'Snoozed'],
            ['key' => 'done',    'label' => 'Erledigt'],
        ];
    }

    protected function items(): array
    {
        return [
            [
                'id' => 1, 'channel' => 'mail', 'channel_label' => 'E-Mail', 'icon' => 'heroicon-o-envelope',
                'sender' => 'Konstantin Broich', 'time' => '09:24', 'unread' => true, 'status' => 'new',
                'subject' => 'Angebot Rheingedeck finalisieren',
                'preview' => 'Hi Martin, können wir das Angebot bis Freitag rausschicken? Offen sind noch …',
                'summary' => 'Konstantin bittet um Finalisierung des Rheingedeck-Angebots bis Freitag. Offene Punkte: Preisstaffel und Liefertermin. Vorgeschlagene Aktion: Angebot prüfen und freigeben.',
                'older' => 2,
                'thread' => [
                    ['from' => 'Martin Erren', 'time' => 'Gestern 17:02', 'body' => 'Passt, ich nehme die Preisstaffel bis morgen früh auf.'],
                    ['from' => 'Konstantin Broich', 'time' => '09:24', 'body' => "Hi Martin,\n\nkönnen wir das Angebot für Rheingedeck bis Freitag rausschicken? Offen sind noch die Preisstaffel ab 500 Stück und der Liefertermin.\n\nMagst du kurz drüberschauen und freigeben?\n\nGruß\nKonstantin"],
                ],
            ],
            [
                'id' => 2, 'channel' => 'meeting', 'channel_label' => 'Meeting', 'icon' => 'heroicon-o-calendar-days',
                'sender' => 'Weekly Sync · Digital Service Theke', 'time' => '14:00', 'unread' => true, 'status' => 'new',
                'subject' => 'Weekly Sync — heute 14:00',
                'preview' => '5 Teilnehmer · Agenda: Roadmap KI-nativer Betrieb, Blocker Support …',
                'summary' => 'Wöchentliches Sync zur Digital Service Theke. Dein Part: Status Change CP-001.',
                'when' => 'Heute 14:00–14:45 · Teams',
                'participants' => ['Christian', 'Sebastian', 'Philip', 'Max', 'Konstantin'],
                'agenda' => ['Roadmap KI-nativer Betrieb (CP-001)', 'Offene Blocker im Support', 'Personal / Skalierung'],
            ],
            [
                'id' => 3, 'channel' => 'task', 'channel_label' => 'Aufgabe', 'icon' => 'heroicon-o-clipboard-document-check',
                'sender' => 'Helpdesk', 'time' => 'Gestern', 'unread' => true, 'status' => 'new',
                'subject' => 'Ticket #142: Drucker Etage 3',
                'preview' => 'Priorität hoch · Der Drucker im 3. OG zieht kein Papier ein …',
                'summary' => 'Helpdesk-Ticket #142 (Priorität hoch): Drucker Etage 3 zieht kein Papier ein. Aktion: übernehmen oder direkt bearbeiten.',
                'props' => [
                    ['Priorität', 'Hoch', 'heroicon-o-flag'],
                    ['Status', 'Neu', 'heroicon-o-inbox'],
                    ['Fällig', 'morgen', 'heroicon-o-calendar-days'],
                    ['Melder', 'Empfang', 'heroicon-o-user'],
                    ['Board', 'IT-Support', 'heroicon-o-rectangle-stack'],
                ],
                'dod' => ['Fehler E-52 reproduzieren', 'Toner & Einzugswalze prüfen', 'Rückmeldung an Empfang'],
            ],
            [
                'id' => 4, 'channel' => 'recording', 'channel_label' => 'Aufnahme', 'icon' => 'heroicon-o-microphone',
                'sender' => 'Kundengespräch · Syltjunkie', 'time' => 'Gestern', 'unread' => false, 'status' => 'new',
                'subject' => 'Transcript: Erstgespräch Syltjunkie',
                'preview' => '32 Min · Themen: Website-Relaunch, Reservierungssystem, Budget …',
                'summary' => 'Erstgespräch Syltjunkie (32 Min). Website-Relaunch gewünscht, Interesse an PausePlus-Reservierung, Budget ~15k. Nächster Schritt: Angebot + Canvas.',
                'duration' => '32:14',
                'segments' => [
                    ['00:02', 'Kunde', 'Wir wollen die Website komplett neu machen.'],
                    ['04:18', 'Martin', 'Die Reservierung könnten wir über PausePlus lösen.'],
                    ['19:40', 'Kunde', 'Budget liegt bei etwa 15.000 Euro.'],
                    ['28:05', 'Martin', 'Ich schicke euch ein Angebot bis nächste Woche.'],
                ],
                'actions' => ['Angebot erstellen', 'Project Canvas anlegen'],
            ],
            [
                'id' => 5, 'channel' => 'message', 'channel_label' => 'Teams', 'icon' => 'heroicon-o-chat-bubble-left',
                'sender' => 'Sebastian', 'time' => 'Mo', 'unread' => false, 'status' => 'new',
                'subject' => 'Frage MDM-Rollout',
                'preview' => 'Sollen wir die neuen Laptops direkt über Intune einrichten oder …',
                'summary' => 'Sebastian fragt, ob die neuen Laptops direkt über Intune (MDM) eingerichtet werden sollen.',
                'chat' => [
                    ['from' => 'Sebastian', 'time' => 'Mo 10:12', 'body' => 'Sollen wir die neuen Laptops direkt über Intune einrichten?', 'me' => false],
                    ['from' => 'Martin', 'time' => 'Mo 10:15', 'body' => 'Ja, gleich MDM-Profil zuweisen.', 'me' => true],
                    ['from' => 'Sebastian', 'time' => 'Mo 10:16', 'body' => 'Top, mach ich.', 'me' => false],
                ],
            ],
            [
                'id' => 8, 'channel' => 'call', 'channel_label' => 'Anruf', 'icon' => 'heroicon-o-phone',
                'sender' => 'Philip Weber', 'time' => '08:47', 'unread' => true, 'status' => 'new',
                'subject' => 'Verpasster Anruf — Philip',
                'preview' => 'Eingehend · verpasst · keine Mailbox …',
                'summary' => 'Verpasster eingehender Anruf von Philip Weber, keine Mailbox. Vorschlag: zurückrufen.',
                'direction_label' => 'Eingehend · verpasst',
                'call_duration' => '0:00',
                'number' => '+49 170 1234567',
            ],
            [
                'id' => 6, 'channel' => 'mail', 'channel_label' => 'E-Mail', 'icon' => 'heroicon-o-envelope',
                'sender' => 'Vodafone Firmenkunden', 'time' => 'Mo', 'unread' => false, 'status' => 'snoozed',
                'subject' => 'Ihre Rechnung Juli 2026',
                'preview' => 'Sehr geehrte Damen und Herren, Ihre aktuelle Rechnung steht bereit …',
                'summary' => 'Vodafone-Mobilfunkrechnung Juli steht bereit. Aktion: über Firmenkundenportal herunterladen und in HGK kontieren.',
                'older' => 0,
                'thread' => [
                    ['from' => 'Vodafone Firmenkunden', 'time' => 'Mo 06:00', 'body' => "Sehr geehrte Damen und Herren,\n\nIhre aktuelle Rechnung für Juli 2026 steht im Firmenkundenportal zum Download bereit.\n\nMit freundlichen Grüßen\nVodafone Firmenkunden"],
                ],
            ],
            [
                'id' => 7, 'channel' => 'system', 'channel_label' => 'System', 'icon' => 'heroicon-o-bell',
                'sender' => 'Academy', 'time' => 'Mo', 'unread' => false, 'status' => 'done',
                'subject' => 'Pflichtkurs fällig: KI-nativer Betrieb — Grundlagen',
                'preview' => 'Fällig in 3 Tagen · Fortschritt 20 %',
                'summary' => 'Pflichtkurs „KI-nativer Betrieb — Grundlagen" ist in 3 Tagen fällig, Fortschritt 20 %. Aktion: Kurs fortsetzen.',
                'body' => "Pflichtkurs: KI-nativer Betrieb — Grundlagen\nFällig: in 3 Tagen\nFortschritt: 20 %\n\nDieser Kurs wurde deiner Rolle zugewiesen. Bitte zeitnah abschließen.",
                'action_label' => 'Kurs fortsetzen',
            ],
        ];
    }

    /**
     * Org-Kontext je Item (an welchen Knoten hängt es, was noch dranhängt) — der Kern:
     * Dinge in die Organisation hängen und Kontext ziehen, nicht kommunizieren.
     */
    protected function contexts(): array
    {
        return [
            1 => ['chips' => [['label' => 'Konstantin Broich', 'icon' => 'heroicon-o-user'], ['label' => 'Rheingedeck', 'icon' => 'heroicon-o-building-storefront']], 'related' => '3 offene Aufgaben · Angebot #12'],
            2 => ['chips' => [['label' => 'Digital Service Theke', 'icon' => 'heroicon-o-rectangle-group'], ['label' => 'Change CP-001', 'icon' => 'heroicon-o-arrows-right-left']], 'related' => 'Change aktiv · 4 Meilensteine'],
            3 => ['chips' => [['label' => 'IT-Support', 'icon' => 'heroicon-o-lifebuoy']], 'related' => 'Ticket #142 · Board IT-Support'],
            4 => ['chips' => [['label' => 'Syltjunkie', 'icon' => 'heroicon-o-building-storefront']], 'related' => 'Neukunde · noch kein Projekt'],
            5 => ['chips' => [['label' => 'IT / Infrastruktur', 'icon' => 'heroicon-o-server-stack']], 'related' => 'MDM-Rollout'],
            6 => ['chips' => [['label' => 'Buchhaltung', 'icon' => 'heroicon-o-banknotes']], 'related' => 'Kontierung HGK'],
            7 => ['chips' => [['label' => 'Academy', 'icon' => 'heroicon-o-academic-cap']], 'related' => 'Pflichtkurs'],
            8 => ['chips' => [['label' => 'Philip Weber', 'icon' => 'heroicon-o-user']], 'related' => 'Marketing'],
        ];
    }

    /**
     * Echte Meeting-Items über den Inbox-Kontrakt (Kanal für Kanal echt).
     * Fehlt Inbox/Team/Daten → leer, dann bleibt der Dummy-Meeting.
     */
    protected function realMeetings(): array
    {
        $contract = \Platform\Inbox\Contracts\InboxMeetingQueryContract::class;
        if (!interface_exists($contract)) {
            return [];
        }

        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return [];
        }

        try {
            $rows = app($contract)->listForUser($user->id, $user->currentTeam->id, 20);
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(fn ($m) => [
            'id'            => 10000 + (int) $m['id'],   // eigener ID-Raum, kollidiert nicht mit Dummies
            'inbox_id'      => (int) $m['id'],
            'real'          => true,
            'channel'       => 'meeting',
            'channel_label' => 'Meeting',
            'icon'          => 'heroicon-o-calendar-days',
            'sender'        => $m['subject'] ?? 'Meeting',
            'subject'       => $m['when'] ?? '',
            'preview'       => trim(($m['participants_count'] ?? 0) . ' Teilnehmer'),
            'time'          => $m['time_short'] ?? '',
            'unread'        => (bool) ($m['unread'] ?? true),
            'status'        => 'new',
            'section'       => $m['section'] ?? null,
            'is_series'     => (bool) ($m['is_series'] ?? false),
            'series_count'  => (int) ($m['series_count'] ?? 1),
        ], $rows);
    }

    public function render()
    {
        $all = $this->items();

        // Meeting-Kanal echt: Dummy-Meetings durch echte ersetzen (falls vorhanden).
        $real = $this->realMeetings();
        if (!empty($real)) {
            $all = array_values(array_filter($all, fn ($it) => ($it['channel'] ?? '') !== 'meeting'));
            $all = array_merge($real, $all);
        }

        // Kanal-Counts aus der tatsächlichen Liste — Zahl und Inhalt passen immer zusammen.
        $counts = ['all' => count($all)];
        foreach ($all as $it) {
            $ch = $it['channel'] ?? '';
            $counts[$ch] = ($counts[$ch] ?? 0) + 1;
        }

        $filtered = array_values(array_filter($all, fn ($it) => $this->matches($it)));

        $selected = null;
        foreach ($all as $it) {
            if ($it['id'] === $this->selectedId) {
                $selected = $it;
                break;
            }
        }

        if ($selected && ($selected['real'] ?? false) && ($selected['channel'] ?? '') === 'meeting') {
            try {
                $detail = app(\Platform\Inbox\Contracts\InboxMeetingQueryContract::class)
                    ->detailForItem((int) $selected['inbox_id']);
                if ($detail) {
                    $selected = array_merge($selected, $detail);
                }
            } catch (\Throwable $e) {
                // Detail nicht verfügbar → Basisdaten aus der Liste bleiben.
            }
        } elseif ($selected) {
            $ctx = $this->contexts()[$selected['id']] ?? [];
            $selected['context'] = $ctx['chips'] ?? [];
            $selected['related'] = $ctx['related'] ?? null;
        }

        return view('home::livewire.inbox', [
            'channels'    => $this->channels($counts),
            'statuses'    => $this->statuses(),
            'items'       => $filtered,
            'selected'    => $selected,
            'nodeResults' => $this->nodeResults(),
        ])->layout('platform::layouts.app');
    }
}
