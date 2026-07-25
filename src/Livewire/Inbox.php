<?php

namespace Platform\Home\Livewire;

use Livewire\Component;

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

    protected function channels(): array
    {
        return [
            ['key' => 'all',       'label' => 'Alle',      'icon' => 'heroicon-o-inbox',                    'count' => 8],
            ['key' => 'mail',      'label' => 'E-Mail',    'icon' => 'heroicon-o-envelope',                 'count' => 2],
            ['key' => 'meeting',   'label' => 'Meeting',   'icon' => 'heroicon-o-calendar-days',            'count' => 1],
            ['key' => 'task',      'label' => 'Aufgabe',   'icon' => 'heroicon-o-clipboard-document-check', 'count' => 1],
            ['key' => 'message',   'label' => 'Teams',     'icon' => 'heroicon-o-chat-bubble-left',         'count' => 1],
            ['key' => 'call',      'label' => 'Anruf',     'icon' => 'heroicon-o-phone',                    'count' => 1],
            ['key' => 'recording', 'label' => 'Aufnahme',  'icon' => 'heroicon-o-microphone',               'count' => 1],
            ['key' => 'system',    'label' => 'System',    'icon' => 'heroicon-o-bell',                     'count' => 1],
        ];
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

    public function render()
    {
        $all = $this->items();

        $filtered = array_values(array_filter($all, fn ($it) => $this->matches($it)));
        $selected = null;
        foreach ($all as $it) {
            if ($it['id'] === $this->selectedId) {
                $selected = $it;
                break;
            }
        }

        return view('home::livewire.inbox', [
            'channels' => $this->channels(),
            'statuses' => $this->statuses(),
            'items'    => $filtered,
            'selected' => $selected,
        ])->layout('platform::layouts.app');
    }
}
