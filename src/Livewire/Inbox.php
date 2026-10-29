<?php

namespace Platform\Home\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Livewire\Concerns\HasPageContext;
use Platform\Home\Livewire\Concerns\ProvidesTodaySummary;

/**
 * HOME-Herzstück – eine Komponente, drei Modi (nx-Stil):
 *   · DASHBOARD (`/` = home.dashboard, renderDashboard → livewire/cockpit): das
 *     warme Zuhause. Begrüßung + Check-in, die präsente aber begrenzte Eingang-Box
 *     (nur offene Items, innen scrollbar; der volle Strom lebt auf /eingang) neben
 *     den Karten „zuletzt gestempelt" / „offene Aufgaben" / „Termine heute". Daten
 *     via ProvidesTodaySummary (+ loadLastStamp) — alles über Person-Kontrakte.
 *   · STROM (`/eingang` = home.inbox, renderStream): der volle Posteingang als
 *     durchlaufende, vollbreite Zeilenliste mit Filtern. Zeile trägt nur Triage;
 *     ein Klick öffnet die Item-View.
 *   · ITEM-VIEW (`/eingang/{item}`, renderDetail → livewire/inbox-item): die eigene
 *     fokussierte Seite eines Items. $itemId steuert; $view trennt dashboard/stream.
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
    use ProvidesTodaySummary;

    /** Detail-Modus: gesetzt = die eigene Item-View (Route /eingang/{item}); null = Stream/Dashboard. */
    public ?int $itemId = null;

    /**
     * Ansicht ohne Item: 'dashboard' (Zuhause, `/` = home.dashboard) oder 'stream'
     * (voller Posteingard, `/eingang` = home.inbox). In mount() aus dem Routennamen
     * abgeleitet; persistiert über Livewire-Updates.
     */
    public string $view = 'stream';

    public string $channel = 'all';
    public string $status = 'all';

    /** Kurze Rückmeldung nach einer Aktion (z. B. „Aufgabe angelegt."). */
    public string $notice = '';

    /** Letzte Triage für das Undo-Toast: ['ids'=>int[], 'label'=>string] oder null. */
    public ?array $lastTriage = null;

    /** Ausgewählte inbox_ids für Massenaktionen im Stream (wire:model). */
    public array $selected = [];

    /** Betreff des Detail-Items als Seitenkontext — siehe pageContext(). */
    protected ?string $itemSubject = null;

    public function mount(?int $item = null): void
    {
        $this->itemId = $item ?: null;

        // Item-View braucht weder Dashboard-Daten noch Tages-Glance.
        if ($this->itemId !== null) {
            return;
        }

        // `/` (home.dashboard) = Zuhause-Dashboard; `/eingang` (home.inbox) = voller Strom.
        $this->view = request()->route()?->getName() === 'home.inbox' ? 'stream' : 'dashboard';

        $this->loadTodaySummary();

        // Die schwereren Widget-Kontrakte nur fürs Dashboard (nicht im /eingang-Kopf).
        if ($this->view === 'dashboard') {
            $this->loadDashboardExtras();
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
        return $this->triage($itemId, fn ($s) => $s->markDone($itemId), 'Erledigt');
    }

    /**
     * Ignorieren: dauerhaft wegdrücken (Status = Ignored) — raus aus dem Eingang,
     * ohne „erledigt". Umkehrbar via Undo (reopen).
     */
    public function ignore(int $itemId)
    {
        return $this->triage($itemId, fn ($s) => $s->ignore($itemId), 'Ignoriert');
    }

    /**
     * Wiedervorlage (Snooze): Einheit für X Stunden aus dem Eingang nehmen; danach
     * taucht sie wieder auf. Status = Snoozed.
     */
    public function snooze(int $itemId, int $hours = 4)
    {
        return $this->triage($itemId, fn ($s) => $s->snooze($itemId, max(1, $hours)), 'Auf Wiedervorlage');
    }

    /**
     * Rückgängig (Undo): die zuletzt triagierte Einheit zurück in den offenen
     * Eingang holen (reopen → Status New). Wird vom Undo-Toast aufgerufen.
     */
    public function undoTriage(): void
    {
        $ids = $this->lastTriage['ids'] ?? [];
        if (! $ids) {
            return;
        }
        try {
            $svc = app(\Platform\Core\Inbox\Services\TriageService::class);
            foreach ($ids as $id) {
                $svc->reopen((int) $id);
            }
        } catch (\Throwable $e) {
            // still
        }
        $this->lastTriage = null;
        $this->dispatch('inbox-undone');
    }

    /** Alle sichtbaren (gefilterten, echten) Items auswählen. */
    public function selectAll(): void
    {
        $all = $this->mergedItems();
        $this->selected = array_values(array_unique(array_map(
            fn ($it) => (int) $it['inbox_id'],
            array_filter($all, fn ($it) => ($it['real'] ?? false) && $this->matches($it))
        )));
    }

    /** Auswahl aufheben. */
    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** Massenaktion: Erledigt auf alle ausgewählten Einheiten. */
    public function bulkDone()
    {
        return $this->bulk(fn ($s, $id) => $s->markDone($id), 'erledigt');
    }

    /** Massenaktion: Ignorieren auf alle ausgewählten Einheiten. */
    public function bulkIgnore()
    {
        return $this->bulk(fn ($s, $id) => $s->ignore($id), 'ignoriert');
    }

    /** Massenaktion: Wiedervorlage auf alle ausgewählten Einheiten. */
    public function bulkSnooze(int $hours = 4)
    {
        return $this->bulk(fn ($s, $id) => $s->snooze($id, max(1, $hours)), 'auf Wiedervorlage');
    }

    /**
     * Gemeinsamer Massen-Triage-Lauf über alle ausgewählten inbox_ids (jede Aktion
     * wirkt ohnehin auf die ganze Einheit; Duplikate sind idempotent). Merkt die
     * Menge fürs Bulk-Undo, feuert das Toast und hebt die Auswahl auf.
     */
    protected function bulk(callable $op, string $verb)
    {
        $ids = array_values(array_unique(array_map('intval', $this->selected)));
        if (! $ids) {
            return null;
        }

        try {
            $svc = app(\Platform\Core\Inbox\Services\TriageService::class);
            foreach ($ids as $id) {
                $op($svc, $id);
            }
            $this->lastTriage = ['ids' => $ids, 'label' => count($ids) . ' ' . $verb];
            $this->dispatch('inbox-triaged', label: count($ids) . ' ' . $verb);
        } catch (\Throwable $e) {
            // still
        }

        $this->selected = [];

        return null;
    }

    /**
     * Gemeinsamer Triage-Lauf: Service best-effort anwenden, die Aktion für das Undo
     * merken + Toast-Event feuern; aus der Detail-View zurück in den Stream navigieren
     * (das Item ist dort ohnehin weg).
     */
    protected function triage(int $itemId, callable $op, string $label)
    {
        try {
            $op(app(\Platform\Core\Inbox\Services\TriageService::class));
            $this->lastTriage = ['ids' => [$itemId], 'label' => $label];
            $this->dispatch('inbox-triaged', label: $label);
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
        // Nur Kanäle, die home tatsächlich rendern kann (= mapRow-Fälle + Core-
        // Provider). message (Teams)/system haben weder Provider noch mapRow — sie
        // liegen zwar in inbox_items, sind aber nicht darstellbar, also hier nicht
        // führen (sonst „Teams 89" ohne Einträge).
        $defs = [['key' => 'all', 'label' => 'Alle', 'icon' => 'heroicon-o-inbox']];
        foreach ($this->renderableChannels() as $c) {
            $defs[] = $c;
        }

        return array_map(fn ($c) => $c + ['count' => $counts[$c['key']] ?? 0], $defs);
    }

    /**
     * Die Kanäle, die home wirklich darstellen kann (Core-SourceList-Provider +
     * recording, und mapRow hat je einen Fall). Steuert Filter-Chips UND Zähler,
     * damit Zahl und Liste zusammenpassen.
     *
     * @return array<int,array{key:string,label:string,icon:string}>
     */
    protected function renderableChannels(): array
    {
        return [
            ['key' => 'mail',      'label' => 'E-Mail',   'icon' => 'heroicon-o-envelope'],
            ['key' => 'meeting',   'label' => 'Meeting',  'icon' => 'heroicon-o-calendar-days'],
            ['key' => 'call',      'label' => 'Anruf',    'icon' => 'heroicon-o-phone'],
            ['key' => 'task',      'label' => 'Aufgabe',  'icon' => 'heroicon-o-clipboard-document-check'],
            ['key' => 'ticket',    'label' => 'Ticket',   'icon' => 'heroicon-o-lifebuoy'],
            ['key' => 'recording', 'label' => 'Aufnahme', 'icon' => 'heroicon-o-microphone'],
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
            $rows = app($contract)->listStreamForUser($user->id, $user->currentTeam->id, 50);
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
        // Nur echte Items aus dem Core-Stream. KEINE Dummies mehr (sie verfälschten
        // Inhalt + Zähler). items() bleibt als Vorlage, wird aber nicht mehr gemischt.
        return $this->consolidateUnits($this->realStream());
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
        if ($this->itemId !== null) {
            return $this->renderDetail();
        }

        return $this->view === 'dashboard' ? $this->renderDashboard() : $this->renderStream();
    }

    /**
     * Zuhause-Dashboard (`/`): warmes Cockpit mit der Eingang-Box (präsent, aber
     * begrenzt), zuletzt gestempelt, offenen Aufgaben, Terminen heute. Die Box zeigt
     * nur OFFENE Items (ein gepflegter Eingang ist nicht voll); der volle Posteingang
     * lebt auf `/eingang`. Triage läuft über dieselben Methoden wie im Strom.
     */
    protected function renderDashboard()
    {
        $open = array_values(array_filter($this->mergedItems(), fn ($it) => ($it['status'] ?? '') !== 'done'));

        // ECHTE Zähler aus inbox_items (ungekappt), nicht aus der pro Kanal
        // limitierten Stream-Liste — sonst stimmen die Badges nicht.
        $counts = $this->openCounts();
        $boxChannels = array_values(array_filter(
            $this->channels($counts),
            fn ($c) => $c['key'] === 'all' || ($c['count'] ?? 0) > 0
        ));

        // Anzeige nach aktivem Kanal filtern (Zähler oben bleiben auf allem Offenen).
        $display = array_values(array_filter(
            $open,
            fn ($it) => $this->channel === 'all' || ($it['channel'] ?? '') === $this->channel
        ));

        $this->itemSubject = null;

        return view('home::livewire.cockpit', [
            'items'       => $display,
            'boxChannels' => $boxChannels,
            'eingangOpen' => (int) ($counts['all'] ?? count($open)),
            'widgets'     => $this->dashboardWidgets(),
        ])->layout('platform::layouts.app');
    }

    /**
     * Echte offene Zähler des Eingangs (pro Kanal + Summe) aus inbox_items über den
     * Core-Kontrakt — unabhängig vom limitierten Stream. ['all'=>int, '<ch>'=>int,…].
     *
     * @return array<string,int>
     */
    protected function openCounts(): array
    {
        $contract = \Platform\Core\Inbox\Contracts\InboxQuery::class;
        $user = Auth::user();
        if (! interface_exists($contract) || ! $user || ! $user->currentTeam) {
            return ['all' => 0];
        }
        try {
            $raw = app($contract)->openCountsForUser($user->id, $user->currentTeam->id);
        } catch (\Throwable $e) {
            return ['all' => 0];
        }
        if (! is_array($raw)) {
            return ['all' => 0];
        }

        // Nur darstellbare Kanäle zählen — message (Teams)/system sind in inbox_items,
        // aber nicht renderbar; sie dürfen weder Chip noch Summe aufblähen.
        $counts = ['all' => 0];
        foreach ($this->renderableChannels() as $ch) {
            $c = (int) ($raw[$ch['key']] ?? 0);
            if ($c > 0) {
                $counts[$ch['key']] = $c;
            }
            $counts['all'] += $c;
        }

        return $counts;
    }

    /**
     * Katalog der sekundären Dashboard-Widgets (unter der Box-Reihe), je nur wenn
     * sein Kontrakt verfügbar ist. Erster Baustein des späteren Abo-Systems: hier
     * würde künftig die User-Auswahl (abonnierte Keys) dazwischenfiltern. Reihenfolge
     * = Anzeige-Reihenfolge; `partials/widgets/{key}.blade.php` rendert jeweils.
     *
     * @return array<int,string>
     */
    protected function dashboardWidgets(): array
    {
        $widgets = [];

        if ($this->calendarAvailable) {
            $widgets[] = 'calendar';
        }
        $widgets[] = 'todos'; // Core — immer verfügbar
        if ($this->goalsAvailable) {
            $widgets[] = 'goals';
        }
        if ($this->coursesAvailable) {
            $widgets[] = 'courses';
        }
        if ($this->orgAvailable && $this->hasPerson && !empty($this->vitalSigns)) {
            $widgets[] = 'kpis';
        }

        return $widgets;
    }

    /** Stream: der Kachel-Fluss mit Filtern. Pinnt keinen Seitenkontext. */
    protected function renderStream()
    {
        $all = $this->mergedItems();

        // ECHTE Kanal-Counts aus inbox_items (ungekappt) — die Stream-Liste ist pro
        // Kanal bei 50 begrenzt, taugt also nicht als Zähler.
        $counts = $this->openCounts();

        $filtered = array_values(array_filter($all, fn ($it) => $this->matches($it)));

        $this->itemSubject = null;

        return view('home::livewire.inbox', [
            'channels'    => $this->channels($counts),
            'items'       => $filtered,
            'eingangOpen' => (int) ($counts['all'] ?? 0),
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
