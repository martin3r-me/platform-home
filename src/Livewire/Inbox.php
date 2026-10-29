<?php

namespace Platform\Home\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Livewire\Concerns\HasPageContext;
use Platform\Home\Livewire\Concerns\ProvidesTodayRail;

/**
 * Cockpit – die persönliche Landefläche von HOME (nx-Stil). Eingang + „Heute"
 * zusammengeführt: links die orientierende Kontext-Schiene (ProvidesTodayRail:
 * Begrüßung, Check-in, Termine heute, Fokus, Knoten-KPIs), in der Mitte der
 * vollbreite Triage-Strom. Outlook-Muster: Kontext-Rail + Nachrichtenliste. Das
 * frühere eigenständige Dashboard ist hierin aufgegangen; `/` und `/eingang`
 * rendern dieselbe Fläche. Reflektierende Tiefe (7-Tage-Zeiten, Ziele, Aufgaben)
 * bleibt auf ihren eigenen Seiten, nur als „Details →" verlinkt.
 *
 * STROM + EIGENE ITEM-VIEW (eine Route, zwei Modi — $itemId steuert):
 *   · STROM (/eingang): ein Fluss aus Kacheln. Die Kachel trägt NUR Triage
 *     (Erledigt/Wiedervorlage, generisch, auf der echten inbox_id); der Klick auf
 *     den Kachelkörper navigiert in die Item-View. Keine In-Place-Expansion.
 *   · ITEM-VIEW (/eingang/{item}): die eigene, fokussierte Seite eines Items —
 *     typ-dispatched (body-dispatch → body-{mail,meeting,recording,…}) samt
 *     Facetten (Aufnahme unter ihrem Meeting), plus die Kontext-Aktionen
 *     (→ Aufgabe/→ Ticket echt; Antworten/Agenda folgen) und „An Knoten hängen"
 *     über das globale Kontext-Panel (HasPageContext → page-context).
 * Triage wirkt auf die ganze Einheit; aus der Item-View geht's danach zurück in
 * den Strom. Daten noch Hybrid (echter Stream via Core-InboxQuery + Dummies für
 * Kanäle ohne Live-Daten). Filter Kanal × Status als schlanke Leiste über dem Strom.
 */
class Inbox extends Component
{
    use HasPageContext;
    use ProvidesTodayRail;

    /** Detail-Modus: gesetzt = die eigene Item-View (Route /eingang/{item}); null = Stream. */
    public ?int $itemId = null;

    public string $channel = 'all';
    public string $status = 'all';

    /** Kurze Rückmeldung nach einer Aktion (z. B. „Aufgabe angelegt."). */
    public string $notice = '';

    /** Betreff des Detail-Items als Seitenkontext — siehe pageContext(). */
    protected ?string $itemSubject = null;

    public function mount(?int $item = null): void
    {
        $this->itemId = $item ?: null;

        // Cockpit-Landing (Stream-Modus): die „Heute"-Schiene füllen. Die Item-View
        // braucht die Schiene nicht — dort spart das die KPI-/Kalender-Queries.
        if ($this->itemId === null) {
            $this->loadTodayRail();
        }
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
     * Erledigt: triagiert → raus aus dem Eingang (Status = Done, wirkt auf die
     * ganze Einheit). Aus dem Stream verschwindet die Kachel beim Re-Render; aus
     * der Detail-View geht's zurück in den Stream. Triage trägt die ECHTE inbox_id
     * (die Kachel reicht $it['inbox_id'] durch; die Detail-View $this->itemId).
     */
    public function markDone(int $itemId)
    {
        return $this->triage(fn ($s) => $s->markDone($itemId));
    }

    /**
     * Wiedervorlage (Snooze): Einheit für X Stunden aus dem Eingang nehmen; danach
     * taucht sie wieder auf. Status = Snoozed.
     */
    public function snooze(int $itemId, int $hours = 4)
    {
        return $this->triage(fn ($s) => $s->snooze($itemId, max(1, $hours)));
    }

    /**
     * Gemeinsamer Triage-Lauf: Service best-effort anwenden; aus der Detail-View
     * zurück in den Stream navigieren (das Item ist dort ohnehin weg).
     */
    protected function triage(callable $op)
    {
        try {
            $op(app(\Platform\Core\Inbox\Services\TriageService::class));
        } catch (\Throwable $e) {
            // still
        }

        if ($this->itemId !== null) {
            return $this->redirect(route('home.inbox'), navigate: true);
        }

        return null;
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

    /** Gemeinsamer Handoff-Aufruf für → Aufgabe / → Ticket (nur in der Detail-View). */
    protected function handoff(string $target, string $okMessage): void
    {
        if (!$this->itemId) {
            return;
        }
        $contract = \Platform\Core\Inbox\Contracts\ItemHandoff::class;
        if (!interface_exists($contract)) {
            return;
        }
        try {
            $ok = app($contract)->handoff($this->itemId, $target);
            $this->notice = $ok ? $okMessage : 'Ziel-Modul nicht verfügbar.';
        } catch (\Throwable $e) {
            // still
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
    protected function detailFor(int $inboxId): array
    {
        $contract = \Platform\Core\Inbox\Contracts\InboxQuery::class;
        if ($inboxId <= 0 || !interface_exists($contract)) {
            return [];
        }
        try {
            $detail = app($contract)->detailForItem($inboxId);

            return is_array($detail) ? $detail : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function render()
    {
        return $this->itemId !== null ? $this->renderDetail() : $this->renderStream();
    }

    /** Stream: der Kachel-Fluss mit Filtern. Pinnt keinen Seitenkontext. */
    protected function renderStream()
    {
        $all = $this->mergedItems();

        // Kanal-Counts aus der tatsächlichen Liste — Zahl und Inhalt passen immer zusammen.
        $counts = ['all' => count($all)];
        foreach ($all as $it) {
            $ch = $it['channel'] ?? '';
            $counts[$ch] = ($counts[$ch] ?? 0) + 1;
        }

        $filtered = array_values(array_filter($all, fn ($it) => $this->matches($it)));

        // Eingang-Zähler für die „Heute"-Schiene — aus derselben Liste, damit Zahl
        // und Strom immer zusammenpassen (unabhängig vom aktiven Filter).
        $eingangOpen = count(array_filter($all, fn ($it) => ($it['status'] ?? '') !== 'done'));
        $eingangUnread = count(array_filter($all, fn ($it) => !empty($it['unread'])));

        $this->itemSubject = null;

        return view('home::livewire.inbox', [
            'channels'      => $this->channels($counts),
            'statuses'      => $this->statuses(),
            'items'         => $filtered,
            'eingangOpen'   => $eingangOpen,
            'eingangUnread' => $eingangUnread,
        ])->layout('platform::layouts.app');
    }

    /**
     * Detail: die eigene View eines Items (Route /eingang/{item}). Zentriert auf den
     * Anker der Einheit (Aufnahme → ihr Meeting), lädt dessen Kanal-Detail + die
     * Facetten und pinnt das Item als Seitenkontext (→ Verknüpfungen/Kontext-Panel).
     */
    protected function renderDetail()
    {
        $item = $this->buildDetail($this->itemId);

        $this->itemSubject = $item
            ? (string) ($item['subject'] ?? $item['sender'] ?? 'Eingang')
            : null;

        return view('home::livewire.inbox-item', [
            'item' => $item,
        ])->layout('platform::layouts.app');
    }

    /**
     * Vollständiges Detail-Array eines Items für die eigene View: Kanal-Detail des
     * Anker-Items + Präsentations-Felder (Icon/Label/Zeit) + die Facetten-Details.
     * Leeres Array, wenn das Item nicht (mehr) existiert.
     *
     * @return array<string,mixed>
     */
    protected function buildDetail(int $inboxId): array
    {
        $anchorId = $this->anchorIdOf($inboxId);
        $detail   = $this->detailFor($anchorId);
        if (!$detail) {
            return [];
        }

        [$icon, $label] = $this->channelMeta($detail['channel'] ?? null);

        $detail['real']          = true;
        $detail['inbox_id']      = $anchorId;
        $detail['icon']          = $icon;
        $detail['channel_label'] = $label;
        $detail['sender']        = $detail['sender_label'] ?? ($detail['sender'] ?? '');
        $detail['time']          = $this->recTime($detail['received_at'] ?? null);
        $detail['facet_details'] = $this->facetDetailsFor($anchorId);

        return $detail;
    }

    /** Anker der Einheit zu einer Item-ID (ohne UnitResolver: die ID selbst). */
    protected function anchorIdOf(int $inboxId): int
    {
        $cls = \Platform\Core\Inbox\Services\UnitResolver::class;
        if (!class_exists($cls)) {
            return $inboxId;
        }
        try {
            return app($cls)->anchorId($inboxId);
        } catch (\Throwable $e) {
            return $inboxId;
        }
    }

    /**
     * Facetten-Details der Einheit (alle Mitglieder außer dem Anker), jeweils als
     * eigener Detail-Block fürs body-dispatch.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function facetDetailsFor(int $anchorId): array
    {
        $cls = \Platform\Core\Inbox\Services\UnitResolver::class;
        if (!class_exists($cls)) {
            return [];
        }
        try {
            $unit = app($cls)->unitFor($anchorId);
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($unit['members'] ?? [] as $m) {
            $mid = (int) ($m['id'] ?? 0);
            if ($mid <= 0 || $mid === $anchorId) {
                continue;
            }
            $d = $this->detailFor($mid);
            if ($d) {
                $out[] = array_merge($d, ['channel' => $m['channel'] ?? ($d['channel'] ?? null), 'inbox_id' => $mid]);
            }
        }

        return $out;
    }

    /**
     * Präsentations-Meta (Icon, Label) je Kanal — für den Detail-Kopf, wo die
     * Stream-mapRow nicht greift.
     *
     * @return array{0:string,1:string}
     */
    protected function channelMeta(?string $channel): array
    {
        return match ($channel) {
            'mail'      => ['heroicon-o-envelope', 'E-Mail'],
            'meeting'   => ['heroicon-o-calendar-days', 'Meeting'],
            'task'      => ['heroicon-o-clipboard-document-check', 'Aufgabe'],
            'ticket'    => ['heroicon-o-lifebuoy', 'Ticket'],
            'message'   => ['heroicon-o-chat-bubble-left', 'Teams'],
            'call'      => ['heroicon-o-phone', 'Anruf'],
            'recording' => ['heroicon-o-microphone', 'Aufnahme'],
            'system'    => ['heroicon-o-bell', 'System'],
            default     => ['heroicon-o-inbox', 'Eingang'],
        };
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
        if (! $this->itemId) {
            return null;
        }

        $model = Relation::getMorphedModel('inbox_item');
        if (! $model || ! class_exists($model)) {
            return null;
        }

        return $model::find($this->itemId);
    }

    protected function pageContextOptions(): array
    {
        return [
            'source'  => 'home.inbox',
            'subject' => $this->itemSubject ?? 'Eingang',
        ];
    }
}
