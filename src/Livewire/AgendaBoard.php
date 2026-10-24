<?php

namespace Platform\Home\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Home\Models\Agenda as AgendaModel;
use Platform\Home\Models\AgendaItem;
use Platform\Home\Models\AgendaMember;
use Platform\Home\Models\AgendaSlot;
use Platform\Home\Services\AgendaService;

/**
 * Agenda-Board – eine Agenda als Kanban (Backlog · eigene Spalten · Erledigt),
 * im selben Stil wie die Planner-Boards. Zugriff nur für Mitglieder; Spalten,
 * Teilen und Löschen für den Besitzer.
 */
class AgendaBoard extends Component
{
    public const TONES = ['indigo', 'amber', 'teal', 'violet', 'sky', 'pink', 'rose', 'emerald'];

    public int $agendaId;
    public bool $showDone = false;

    // Karte bearbeiten
    public bool $showItem = false;
    public ?int $editId = null;
    public string $editTitle = '';
    public string $editNotes = '';
    public ?string $editDate = null;

    // Einstellungen (Name, Beschreibung, Mitglieder)
    public bool $showSettings = false;
    public string $settingsName = '';
    public string $settingsDescription = '';

    public function mount(int $agenda): void
    {
        abort_unless($this->findAgenda($agenda), 404);
        $this->agendaId = $agenda;
    }

    // ── Daten ────────────────────────────────────────────────

    #[Computed]
    public function agenda(): ?AgendaModel
    {
        return $this->findAgenda($this->agendaId);
    }

    #[Computed]
    public function isOwner(): bool
    {
        return $this->agenda && app(AgendaService::class)->roleOf($this->agenda, Auth::id()) === 'owner';
    }

    /** Spalten fürs Board: backlog, Slots, done — je mit formatierten Karten. */
    #[Computed]
    public function columns(): array
    {
        $agenda = $this->agenda;
        if (! $agenda) {
            return [];
        }

        $service = app(AgendaService::class);
        $open = $agenda->items()->where('is_done', false)->ordered()->get();
        $done = $agenda->items()->where('is_done', true)->orderByDesc('updated_at')->limit(50)->get();
        $cards = collect($service->formatItems($open->concat($done)))->keyBy('id');
        $pick = fn ($items) => $items->map(fn ($i) => $cards[$i->id])->values()->all();

        $slots = $agenda->slots()->orderBy('order')->get();
        $columns = [[
            'key' => 'backlog', 'id' => null, 'name' => 'Backlog', 'tone' => 'slate',
            'items' => $pick($open->whereNull('agenda_slot_id')),
        ]];
        foreach ($slots->values() as $i => $slot) {
            $columns[] = [
                'key' => (string) $slot->id, 'id' => $slot->id, 'name' => $slot->name,
                'tone' => in_array($slot->color, self::TONES, true) ? $slot->color : self::TONES[$i % count(self::TONES)],
                'items' => $pick($open->where('agenda_slot_id', $slot->id)),
            ];
        }
        // Items in gelöschten/fremden Slots fallen in den Backlog
        $known = $slots->pluck('id')->all();
        $orphans = $open->filter(fn ($i) => $i->agenda_slot_id && ! in_array($i->agenda_slot_id, $known, true));
        $columns[0]['items'] = array_merge($columns[0]['items'], $pick($orphans));

        $columns[] = ['key' => 'done', 'id' => null, 'name' => 'Erledigt', 'tone' => 'emerald', 'items' => $pick($done)];

        return $columns;
    }

    #[Computed]
    public function members(): array
    {
        if (! $this->agenda) {
            return [];
        }

        return AgendaMember::where('agenda_id', $this->agendaId)
            ->with('user:id,name,email,avatar')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->user_id,
                'name' => $m->user?->name ?? $m->user?->email ?? 'Unbekannt',
                'avatar' => $m->user && method_exists($m->user, 'avatarUrl') ? $m->user->avatarUrl() : null,
                'role' => $m->role,
            ])
            ->sortBy(fn ($m) => ($m['role'] === 'owner' ? '0' : '1') . mb_strtolower($m['name']))
            ->values()
            ->all();
    }

    /** Team-Mitglieder, die noch nicht auf der Agenda sind (zum Teilen). */
    #[Computed]
    public function candidates(): array
    {
        $team = $this->agenda?->team;
        if (! $team || ! $this->isOwner) {
            return [];
        }
        $memberIds = array_column($this->members, 'id');

        return $team->users()
            ->whereNotIn('users.id', $memberIds)
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name ?: $u->email])
            ->all();
    }

    // ── Karten ───────────────────────────────────────────────

    public function createItem(?string $column, string $title): void
    {
        $agenda = $this->agenda;
        $title = trim($title);
        if (! $agenda || $title === '') {
            return;
        }

        $slotId = $this->slotIdFor($column);
        $agenda->items()->create([
            'agenda_slot_id' => $slotId,
            'title' => mb_substr($title, 0, 255),
            'sort_order' => ($agenda->items()->where('agenda_slot_id', $slotId)->max('sort_order') ?? 0) + 1,
        ]);
        $agenda->refreshItemCount();
        $this->refresh();
    }

    public function toggleDone(int $itemId): void
    {
        if ($item = $this->item($itemId)) {
            $item->update(['is_done' => ! $item->is_done]);
            $this->agenda->refreshItemCount();
            $this->refresh();
        }
    }

    public function editItem(int $itemId): void
    {
        $item = $this->item($itemId);
        if (! $item) {
            return;
        }

        $this->editId = $item->id;
        $this->editTitle = (string) $item->title;
        $this->editNotes = (string) $item->notes;
        $this->editDate = $item->date?->toDateString();
        $this->showItem = true;
    }

    public function saveItem(): void
    {
        $item = $this->editId ? $this->item($this->editId) : null;
        if (! $item || trim($this->editTitle) === '') {
            return;
        }

        $this->validate(['editDate' => 'nullable|date']);
        $item->update([
            'title' => mb_substr(trim($this->editTitle), 0, 255),
            'notes' => trim($this->editNotes) ?: null,
            'date' => $this->editDate ?: null,
        ]);
        $this->showItem = false;
        $this->refresh();
    }

    public function deleteItem(int $itemId): void
    {
        if ($item = $this->item($itemId)) {
            $item->delete();
            $this->agenda->refreshItemCount();
        }
        $this->showItem = false;
        $this->editId = null;
        $this->refresh();
    }

    /** Drag & Drop: Karten zwischen/innerhalb der Spalten (livewire-sortable Gruppen-Payload). */
    public function updateItemOrder(array $groups): void
    {
        $agenda = $this->agenda;
        if (! $agenda) {
            return;
        }

        $slotIds = $agenda->slots()->pluck('id')->all();
        foreach ($groups as $group) {
            $key = (string) ($group['value'] ?? '');
            $isDone = $key === 'done';
            $slotId = $this->slotIdFor($key);
            if ($slotId && ! in_array($slotId, $slotIds, true)) {
                continue;
            }

            foreach ($group['items'] ?? [] as $entry) {
                $agenda->items()->whereKey((int) $entry['value'])->update([
                    'agenda_slot_id' => $isDone ? null : $slotId,
                    'is_done' => $isDone,
                    'sort_order' => (int) $entry['order'],
                ]);
            }
        }
        $agenda->refreshItemCount();
        $this->refresh();
    }

    // ── Spalten ──────────────────────────────────────────────

    public function createSlot(string $name): void
    {
        $name = trim($name);
        if (! $this->isOwner || $name === '') {
            return;
        }

        AgendaSlot::create([
            'agenda_id' => $this->agendaId,
            'name' => mb_substr($name, 0, 120),
            'order' => (AgendaSlot::where('agenda_id', $this->agendaId)->max('order') ?? 0) + 1,
        ]);
        $this->refresh();
    }

    public function renameSlot(int $slotId, string $name): void
    {
        $name = trim($name);
        if ($this->isOwner && $name !== '') {
            $this->slot($slotId)?->update(['name' => mb_substr($name, 0, 120)]);
            $this->refresh();
        }
    }

    public function setSlotTone(int $slotId, string $tone): void
    {
        if ($this->isOwner && in_array($tone, self::TONES, true)) {
            $this->slot($slotId)?->update(['color' => $tone]);
            $this->refresh();
        }
    }

    /** Spalte löschen — ihre Karten wandern in den Backlog. */
    public function deleteSlot(int $slotId): void
    {
        $slot = $this->isOwner ? $this->slot($slotId) : null;
        if ($slot) {
            $slot->items()->update(['agenda_slot_id' => null]);
            $slot->delete();
            $this->refresh();
        }
    }

    public function updateSlotOrder(array $slots): void
    {
        if (! $this->isOwner) {
            return;
        }

        foreach ($slots as $entry) {
            if (is_numeric($entry['value'] ?? null)) {
                AgendaSlot::where('agenda_id', $this->agendaId)->whereKey((int) $entry['value'])
                    ->update(['order' => (int) $entry['order']]);
            }
        }
        $this->refresh();
    }

    // ── Einstellungen & Teilen ───────────────────────────────

    public function openSettings(): void
    {
        $this->settingsName = (string) $this->agenda?->name;
        $this->settingsDescription = (string) $this->agenda?->description;
        $this->showSettings = true;
    }

    public function saveSettings(): void
    {
        if (! $this->isOwner || trim($this->settingsName) === '') {
            return;
        }

        $this->agenda->update([
            'name' => mb_substr(trim($this->settingsName), 0, 120),
            'description' => trim($this->settingsDescription) ?: null,
        ]);
        $this->showSettings = false;
        $this->refresh();
    }

    public function addMember(int $userId): void
    {
        if (! $this->isOwner || ! in_array($userId, array_column($this->candidates, 'id'), true)) {
            return;
        }

        AgendaMember::firstOrCreate(['agenda_id' => $this->agendaId, 'user_id' => $userId], ['role' => 'member']);
        unset($this->members, $this->candidates);
    }

    public function removeMember(int $userId): void
    {
        if (! $this->isOwner || $userId === Auth::id()) {
            return;
        }

        AgendaMember::where('agenda_id', $this->agendaId)->where('user_id', $userId)->delete();
        unset($this->members, $this->candidates);
    }

    /** Geteilte Agenda verlassen (Besitzer löschen stattdessen). */
    public function leave()
    {
        if ($this->isOwner) {
            return null;
        }

        AgendaMember::where('agenda_id', $this->agendaId)->where('user_id', Auth::id())->delete();

        return $this->redirectRoute('home.agenda', navigate: true);
    }

    public function deleteAgenda()
    {
        if (! $this->isOwner) {
            return null;
        }

        $agenda = $this->agenda;
        $agenda->items()->delete();
        $agenda->slots()->delete();
        $agenda->members()->delete();
        $agenda->delete();

        return $this->redirectRoute('home.agenda', navigate: true);
    }

    // ── Helfer ───────────────────────────────────────────────

    protected function findAgenda(int $id): ?AgendaModel
    {
        $user = Auth::user();

        $service = app(AgendaService::class);

        return $user ? $service->findFor($id, $user->id, $service->teamIdFor($user)) : null;
    }

    protected function item(int $id): ?AgendaItem
    {
        return $this->agenda?->items()->whereKey($id)->first();
    }

    protected function slot(int $id): ?AgendaSlot
    {
        return AgendaSlot::where('agenda_id', $this->agendaId)->whereKey($id)->first();
    }

    /** Spalten-Schlüssel → Slot-ID ('backlog'/'done'/leer = null). */
    protected function slotIdFor(?string $column): ?int
    {
        return is_numeric($column) && (int) $column > 0 ? (int) $column : null;
    }

    protected function refresh(): void
    {
        unset($this->agenda, $this->columns, $this->isOwner);
    }

    public function render()
    {
        $columns = $this->columns;

        return view('home::livewire.agenda-board', [
            'backlog' => $columns[0] ?? null,
            'slotColumns' => array_slice($columns, 1, -1),
            'done' => end($columns) ?: null,
            'tones' => self::TONES,
        ])->layout('platform::layouts.app');
    }
}
