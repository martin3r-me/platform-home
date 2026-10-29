<?php

namespace Platform\Home\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Livewire\Concerns\HasPageContext;

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
    use HasPageContext;

    public ?int $selectedId = 1;
    public string $channel = 'all';
    public string $status = 'all';

    /** Kurze Rückmeldung nach einer Aktion (z. B. „Aufgabe angelegt."). */
    public string $notice = '';

    /** Gewähltes echtes Item (inbox_id + Betreff) als Seitenkontext — siehe pageContext(). */
    protected ?int $selectedInboxId = null;
    protected ?string $selectedSubject = null;

    public function mount(): void
    {
        $this->selectFirst();
    }

    public function selectItem(int $id): void
    {
        $this->selectedId = $id;
        $this->notice = '';
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
        $this->selectFirst();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->selectFirst();
    }

    /**
     * Erledigt: triagiert → raus aus dem Eingang. Setzt den Inbox-Status auf Done;
     * listForUser zieht nur 'new', also verschwindet das Item. Knoten-Link und
     * Zeit-Buchung bleiben bestehen (MaterializeMeetingTime filtert nicht nach
     * Inbox-Status).
     */
    public function markDone(): void
    {
        $id = $this->currentRealItemId();
        if (!$id) {
            return;
        }
        try {
            app(\Platform\Core\Inbox\Services\TriageService::class)->markDone($id);
        } catch (\Throwable $e) {
            // still
        }

        // Item ist raus → Auswahl leeren, nächster wird rechts gewählt.
        $this->selectedId = null;
    }

    /**
     * Snooze: Item (samt Thread/Serie) für X Stunden aus dem Eingang nehmen.
     * listForUser blendet Items mit snoozed_until in der Zukunft aus; danach
     * taucht die Einheit wieder auf. Status = Snoozed (nicht „handled").
     */
    public function snooze(int $hours = 4): void
    {
        $id = $this->currentRealItemId();
        if (!$id) {
            return;
        }
        try {
            app(\Platform\Core\Inbox\Services\TriageService::class)->snooze($id, $hours);
        } catch (\Throwable $e) {
            // still
        }

        $this->selectedId = null;
    }

    /**
     * Aufnahme einem Termin zuordnen (manuell, aus den Vorschlägen im Pane).
     * Ersetzt eine bestehende Zuordnung und reicht die Org-Verknüpfungen des
     * Termins an die Aufnahme durch. Detail lädt beim Re-Render neu.
     */
    public function assignRecording(int $recordingInboxId, int $meetingInboxId): void
    {
        $contract = \Platform\Core\Inbox\Contracts\ItemLinking::class;
        if (!interface_exists($contract)) {
            return;
        }
        try {
            app($contract)->assign($recordingInboxId, $meetingInboxId);
        } catch (\Throwable $e) {
            // still — Zuordnung nicht möglich
        }
    }

    /** Termin-Zuordnung einer Aufnahme wieder lösen. */
    public function unassignRecording(int $recordingInboxId): void
    {
        $contract = \Platform\Core\Inbox\Contracts\ItemLinking::class;
        if (!interface_exists($contract)) {
            return;
        }
        try {
            app($contract)->unassign($recordingInboxId);
        } catch (\Throwable $e) {
            // still
        }
    }

    /**
     * → Aufgabe: aus dem Item eine Planner-Aufgabe machen, über die Core-ItemHandoff-
     * Naht (idempotent, Ziel-Modul-blind). Das Item bleibt im Eingang, bis erledigt.
     */
    public function toTask(): void
    {
        $this->handoff('task', 'Aufgabe angelegt.');
    }

    /** → Ticket: aus dem Item ein Helpdesk-Ticket machen (Handoff, idempotent). */
    public function toTicket(): void
    {
        $this->handoff('ticket', 'Ticket angelegt.');
    }

    /** Gemeinsamer Handoff-Aufruf für → Aufgabe / → Ticket. */
    protected function handoff(string $target, string $okMessage): void
    {
        $id = $this->currentRealItemId();
        if (!$id) {
            return;
        }
        $contract = \Platform\Core\Inbox\Contracts\ItemHandoff::class;
        if (!interface_exists($contract)) {
            return;
        }
        try {
            $ok = app($contract)->handoff($id, $target);
            $this->notice = $ok ? $okMessage : 'Ziel-Modul nicht verfügbar.';
        } catch (\Throwable $e) {
            // still
        }
    }

    /**
     * Reale Inbox-Item-ID des aktuell gewählten Items (oder null bei Dummy/keins).
     * Echte Items liegen im ID-Raum ab 10000 (Kanal-Offset je 10000) — die reale
     * inbox_id ist der Rest modulo 10000.
     */
    protected function currentRealItemId(): ?int
    {
        if (!$this->selectedId || $this->selectedId < 10000) {
            return null;
        }
        return $this->selectedId % 10000;
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
            ['key' => 'ticket',    'label' => 'Ticket',    'icon' => 'heroicon-o-lifebuoy'],
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
     * Echter Eingang-Stream über die EINE Core-InboxQuery (listStreamForUser):
     * Meeting/Mail/Call/Task/Ticket via SourceList-Adapter, Aufnahme Core-nativ.
     * home kennt die 5 Inbox*QueryContracts nicht mehr — nur noch Core. Die
     * kanal-spezifische Präsentation (Icon/Label/Felder) macht mapRow().
     */
    protected function realStream(): array
    {
        $contract = \Platform\Core\Inbox\Contracts\InboxQuery::class;
        if (!interface_exists($contract)) {
            return [];
        }

        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return [];
        }

        try {
            $rows = app($contract)->listStreamForUser($user->id, $user->currentTeam->id, 25);
        } catch (\Throwable $e) {
            return [];
        }

        $mapped = [];
        foreach ($rows as $r) {
            $item = $this->mapRow(is_array($r) ? $r : []);
            if ($item !== null) {
                $mapped[] = $item;
            }
        }

        return $mapped;
    }

    /**
     * Eine Core-Stream-Zeile → home-Eingang-Item (reine Präsentation). Je Kanal
     * eigener ID-Raum (Offset), Icon und Label; die Felder kommen aus der kanal-
     * spezifischen Listen-Form des jeweiligen SourceList-Providers.
     *
     * @param  array<string,mixed>  $m
     * @return array<string,mixed>|null
     */
    protected function mapRow(array $m): ?array
    {
        $channel = $m['channel'] ?? null;
        $inboxId = (int) ($m['id'] ?? 0);
        if ($inboxId <= 0 || !$channel) {
            return null;
        }

        return match ($channel) {
            'meeting' => [
                'id'            => 10000 + $inboxId,
                'inbox_id'      => $inboxId,
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
            ],
            'mail' => [
                'id'            => 20000 + $inboxId,
                'inbox_id'      => $inboxId,
                'real'          => true,
                'channel'       => 'mail',
                'channel_label' => 'E-Mail',
                'icon'          => 'heroicon-o-envelope',
                'sender'        => $m['sender'] ?? 'Unbekannt',
                'subject'       => $m['subject'] ?? '',
                'preview'       => $m['preview'] ?? '',
                'time'          => $m['time'] ?? '',
                'unread'        => (bool) ($m['unread'] ?? true),
                'status'        => 'new',
                'is_thread'     => ($m['thread_count'] ?? 1) > 1,
                'thread_count'  => (int) ($m['thread_count'] ?? 1),
            ],
            'call' => [
                'id'            => 30000 + $inboxId,
                'inbox_id'      => $inboxId,
                'real'          => true,
                'channel'       => 'call',
                'channel_label' => 'Anruf',
                'icon'          => 'heroicon-o-phone',
                'sender'        => $m['sender'] ?? 'Unbekannt',
                'subject'       => $m['subject'] ?? '',
                'preview'       => $m['preview'] ?? '',
                'time'          => $m['time'] ?? '',
                'unread'        => (bool) ($m['unread'] ?? true),
                'status'        => 'new',
            ],
            'task' => [
                'id'            => 40000 + $inboxId,
                'inbox_id'      => $inboxId,
                'real'          => true,
                'channel'       => 'task',
                'channel_label' => 'Aufgabe',
                'icon'          => 'heroicon-o-clipboard-document-check',
                'sender'        => $m['sender'] ?? 'Zugewiesen',
                'subject'       => $m['subject'] ?? '',
                'preview'       => $m['preview'] ?? '',
                'time'          => $m['time'] ?? '',
                'unread'        => (bool) ($m['unread'] ?? true),
                'status'        => 'new',
            ],
            'ticket' => [
                'id'            => 50000 + $inboxId,
                'inbox_id'      => $inboxId,
                'real'          => true,
                'channel'       => 'ticket',
                'channel_label' => 'Ticket',
                'icon'          => 'heroicon-o-lifebuoy',
                'sender'        => $m['sender'] ?? 'Zugewiesen',
                'subject'       => $m['subject'] ?? '',
                'preview'       => $m['preview'] ?? '',
                'time'          => $m['time'] ?? '',
                'unread'        => (bool) ($m['unread'] ?? true),
                'status'        => 'new',
            ],
            'recording' => [
                'id'            => 60000 + $inboxId,
                'inbox_id'      => $inboxId,
                'real'          => true,
                'channel'       => 'recording',
                'channel_label' => 'Aufnahme',
                'icon'          => 'heroicon-o-microphone',
                'sender'        => $m['sender_label'] ?? 'Aufnahme',
                'subject'       => $m['subject'] ?? '',
                'preview'       => $m['preview'] ?? '',
                'time'          => $this->recTime($m['received_at'] ?? null),
                'unread'        => true,
                'status'        => 'new',
            ],
            default => null,
        };
    }

    /** ISO-Zeit → kurzes Label (heute: H:i, sonst Datum). */
    protected function recTime(?string $iso): string
    {
        if (!$iso) {
            return '';
        }
        try {
            $c = \Illuminate\Support\Carbon::parse($iso);

            return $c->isToday() ? $c->format('H:i') : $c->locale('de')->isoFormat('dd D.M.');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Die gemergte Gesamtliste (echte Items + Dummies), ungefiltert. */
    protected function mergedItems(): array
    {
        $all = $this->items();
        $real = $this->realStream();

        // Dummies der Kanäle, für die es echte Items gibt, raus; echte davor.
        $realChannels = array_unique(array_filter(array_map(fn ($it) => $it['channel'] ?? null, $real)));
        if ($realChannels) {
            $all = array_values(array_filter($all, fn ($it) => !in_array($it['channel'] ?? '', $realChannels, true)));
        }

        return $this->consolidateUnits(array_merge($real, $all));
    }

    /**
     * Einheit-Konsolidierung (2→1): absorbierte Mitglieder ausblenden, den Anker
     * mit seinen Facetten annotieren. Nutzt den quellen-agnostischen Core-
     * UnitResolver (Keys ∪ Links). Key-Gruppen (Mail-Thread/Meeting-Serie) falten
     * bereits die Quell-Contracts — hier kommt die Link-Faltung dazu (Aufnahme
     * unter Meeting). Ohne UnitResolver unverändert zurück.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<int,array<string,mixed>>
     */
    protected function consolidateUnits(array $items): array
    {
        $resolverClass = \Platform\Core\Inbox\Services\UnitResolver::class;
        if (!class_exists($resolverClass)) {
            return $items;
        }
        try {
            $resolver = app($resolverClass);
        } catch (\Throwable $e) {
            return $items;
        }

        // Präsente echte Item-IDs → Core löst Anker + Facetten auf (nur Verlinktes).
        $presentIds = [];
        foreach ($items as $it) {
            if (!empty($it['inbox_id'])) {
                $presentIds[] = (int) $it['inbox_id'];
            }
        }
        if (!$presentIds) {
            return $items;
        }

        try {
            $res = $resolver->consolidate($presentIds);
        } catch (\Throwable $e) {
            return $items;
        }
        $anchorOf = $res['anchorOf'];   // inbox_id => Anker (self, wenn Anker fehlt)
        $facetsOf = $res['facetsOf'];   // Anker => [['channel','inbox_id'], …]

        // Nur Anker (oder Self-Anker) behalten; Dummies unverändert durchlassen.
        $out = [];
        foreach ($items as $it) {
            $iid = (int) ($it['inbox_id'] ?? 0);
            if ($iid <= 0) {
                $out[] = $it;
                continue;
            }
            if (($anchorOf[$iid] ?? $iid) !== $iid) {
                continue; // absorbiertes Mitglied → ausgeblendet
            }
            if (!empty($facetsOf[$iid])) {
                $it['facets'] = $facetsOf[$iid];
            }
            $out[] = $it;
        }

        return $out;
    }

    /**
     * Kanal-Detail eines Items (Anker oder Facette) über die EINE Core-InboxQuery.
     * StreamQuery::detailForItem merged pro Kanal den registrierten SourceDetail-
     * Provider dazu (Meeting/Mail/Call/Task/Ticket via Adapter, Aufnahme nativ) —
     * home braucht die 5 Inbox*QueryContracts dafür nicht mehr.
     *
     * @return array<string,mixed>
     */
    protected function detailFor(?string $channel, int $inboxId): array
    {
        $contract = \Platform\Core\Inbox\Contracts\InboxQuery::class;
        if (!$channel || $inboxId <= 0 || !interface_exists($contract)) {
            return [];
        }
        try {
            $detail = app($contract)->detailForItem($inboxId);

            return is_array($detail) ? $detail : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Vorselektion: das erste Item der aktuellen Kanal/Status-Ansicht wählen. */
    protected function selectFirst(): void
    {
        $filtered = array_values(array_filter($this->mergedItems(), fn ($it) => $this->matches($it)));
        $this->selectedId = $filtered[0]['id'] ?? null;
    }

    public function render()
    {
        $all = $this->mergedItems();

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

        if ($selected && ($selected['real'] ?? false)) {
            // Anker-Detail nachladen …
            $selected = array_merge(
                $selected,
                $this->detailFor($selected['channel'] ?? null, (int) $selected['inbox_id'])
            );

            // … und die Facetten der Einheit (z.B. die Aufnahme unter dem Meeting)
            // als eigene Detail-Blöcke stapeln.
            $facetDetails = [];
            foreach ($selected['facets'] ?? [] as $facet) {
                $fid = (int) ($facet['inbox_id'] ?? 0);
                $fch = $facet['channel'] ?? null;
                if ($fid <= 0 || ! $fch) {
                    continue;
                }
                $detail = $this->detailFor($fch, $fid);
                if ($detail) {
                    $facetDetails[] = array_merge($detail, ['channel' => $fch, 'inbox_id' => $fid]);
                }
            }
            $selected['facet_details'] = $facetDetails;
        }

        // Gewähltes echtes Item als Seitenkontext merken → HasPageContext::rendered()
        // dispatcht daraus `page-context` (neues Kontext-Panel, inkl. „Verknüpfungen"/
        // DimensionLinks zum Hängen an einen Org-Knoten) plus die Alt-Events (comms …).
        // Nur „reale" Items mit inbox_id; Dummy/kein Item → Kontext zurücksetzen.
        if ($selected && ($selected['real'] ?? false) && ! empty($selected['inbox_id'])) {
            $this->selectedInboxId = (int) $selected['inbox_id'];
            $this->selectedSubject = (string) ($selected['subject'] ?? $selected['sender'] ?? 'Eingang');
        } else {
            $this->selectedInboxId = null;
            $this->selectedSubject = null;
        }

        return view('home::livewire.inbox', [
            'channels'    => $this->channels($counts),
            'statuses'    => $this->statuses(),
            'items'       => $filtered,
            'selected'    => $selected,
        ])->layout('platform::layouts.app');
    }

    /**
     * Seitenkontext für das Kontext-Panel + Terminal (Trait HasPageContext). Das
     * gewählte echte Inbox-Item wird zum Kontext; daraus leitet der Trait die
     * verfügbaren Apps ab — u. a. „Verknüpfungen" (DimensionLinks), über die das Item
     * an einen Org-Knoten gehängt wird. Kein/Dummy-Item → null → Kontext zurückgesetzt.
     *
     * Soft-gekoppelt: die FQCN kommt über den Morph-Alias `inbox_item` (von Core,
     * InboxEngineServiceProvider registriert) — home referenziert die Inbox-Klasse
     * nicht direkt. Fehlt der Morph-Eintrag → null → kein Pin (graceful).
     */
    protected function pageContext(): ?Model
    {
        if (! $this->selectedInboxId) {
            return null;
        }

        $model = Relation::getMorphedModel('inbox_item');
        if (! $model || ! class_exists($model)) {
            return null;
        }

        return $model::find($this->selectedInboxId);
    }

    protected function pageContextOptions(): array
    {
        return [
            'source'  => 'home.inbox',
            'subject' => $this->selectedSubject ?? 'Eingang',
        ];
    }
}
