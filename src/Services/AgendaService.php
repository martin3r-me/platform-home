<?php

namespace Platform\Home\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Platform\Core\Contracts\AgendaRenderable;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Home\Models\Agenda;
use Platform\Home\Models\AgendaItem;
use Platform\Home\Models\AgendaMember;

/**
 * Agenden — persönliche/geteilte Boards, auf die man Dinge aus allen Modulen legt.
 *
 * Gemeinsame Logik für das Board in `home` und den Agenda-Reiter im Kontext-Panel.
 * Zugriff immer über Mitgliedschaft (terminal_agenda_members); die Tabellen heißen
 * aus historischen Gründen noch terminal_*.
 */
class AgendaService
{
    private const TYPE_LABELS = [
        'Platform\\Planner\\Models\\PlannerTask' => 'Aufgabe',
        'Platform\\Planner\\Models\\PlannerProject' => 'Projekt',
        'Platform\\Planner\\Models\\PlannerProjectCanvas' => 'Canvas',
        'Platform\\Helpdesk\\Models\\HelpdeskBoard' => 'Board',
        'Platform\\Helpdesk\\Models\\HelpdeskTicket' => 'Ticket',
        'Platform\\Okr\\Models\\Objective' => 'Objective',
        'Platform\\Okr\\Models\\KeyResult' => 'Key Result',
        'Platform\\Brands\\Models\\BrandsBrand' => 'Marke',
        'Platform\\Organization\\Models\\OrganizationEntity' => 'Einheit',
        'Platform\\Crm\\Models\\CrmCompany' => 'Firma',
        'Platform\\Crm\\Models\\CrmContact' => 'Kontakt',
    ];

    /**
     * Team der Agenden: immer das Basis-Team des Users (current_team_id). currentTeam
     * hängt vom URL-Segment ab (root-scoped Module → Root-Team) und wäre zwischen
     * Seitenaufruf, Livewire-Update und Kontext-Panel nicht stabil.
     */
    public function teamIdFor(?object $user): ?int
    {
        return $user?->current_team_id ? (int) $user->current_team_id : null;
    }

    /** Agenden des Users im Team, nach Name. */
    public function agendasFor(int $userId, ?int $teamId): Collection
    {
        if (! $teamId) {
            return collect();
        }

        return Agenda::forTeam($teamId)
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->with(['members' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('name')
            ->get();
    }

    /** Agenda nur, wenn der User Mitglied ist (und sie im Team liegt). */
    public function findFor(int $agendaId, int $userId, ?int $teamId): ?Agenda
    {
        if (! $teamId) {
            return null;
        }

        return Agenda::forTeam($teamId)
            ->whereKey($agendaId)
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->first();
    }

    public function roleOf(Agenda $agenda, int $userId): ?string
    {
        return AgendaMember::where('agenda_id', $agenda->id)->where('user_id', $userId)->value('role');
    }

    public function create(int $teamId, int $userId, string $name, ?string $description = null): Agenda
    {
        $agenda = Agenda::create([
            'team_id' => $teamId,
            'name' => trim($name),
            'description' => $description ? trim($description) : null,
            'created_by_user_id' => $userId,
        ]);

        AgendaMember::create(['agenda_id' => $agenda->id, 'user_id' => $userId, 'role' => 'owner']);

        return $agenda;
    }

    /** Agenda-IDs, auf denen das Objekt liegt (nur Agenden, die der User sieht). */
    public function agendaIdsContaining(string $type, int $id, int $userId, ?int $teamId): array
    {
        $ids = $this->agendasFor($userId, $teamId)->pluck('id');
        if ($ids->isEmpty()) {
            return [];
        }

        return AgendaItem::whereIn('agenda_id', $ids)
            ->where('agendable_type', $type)
            ->where('agendable_id', $id)
            ->pluck('agenda_id')
            ->unique()
            ->values()
            ->all();
    }

    /** Legt ein Objekt auf die Agenda (in den Backlog) — doppelt gibt es nicht. */
    public function attach(Agenda $agenda, string $type, int $id, ?string $fallbackTitle = null): ?AgendaItem
    {
        $existing = $agenda->items()->where('agendable_type', $type)->where('agendable_id', $id)->first();
        if ($existing) {
            return $existing;
        }

        $model = class_exists($type) ? $type::find($id) : null;
        if (! $model) {
            return null;
        }

        $rendered = $model instanceof AgendaRenderable ? $model->toAgendaItem() : [];

        $item = $agenda->items()->create([
            'agendable_type' => $type,
            'agendable_id' => $id,
            'title' => $rendered['title'] ?? $this->titleOf($model) ?? $fallbackTitle ?? class_basename($type) . ' #' . $id,
            'color' => $rendered['color'] ?? null,
            'sort_order' => ($agenda->items()->whereNull('agenda_slot_id')->max('sort_order') ?? 0) + 1,
        ]);
        $agenda->refreshItemCount();

        return $item;
    }

    public function detach(Agenda $agenda, string $type, int $id): void
    {
        $agenda->items()->where('agendable_type', $type)->where('agendable_id', $id)->delete();
        $agenda->refreshItemCount();
    }

    public function typeLabel(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return self::TYPE_LABELS[$type] ?? 'Verknüpft';
    }

    /**
     * Karten-Daten für eine Liste von Items. Verknüpfte Objekte werden je Typ in
     * einer Abfrage geladen; fehlt ein Modul/Objekt, bleibt die Karte einfach unverknüpft.
     *
     * @param  iterable<AgendaItem>  $items
     * @return array<int, array<string, mixed>>
     */
    public function formatItems(iterable $items): array
    {
        $items = collect($items);
        $models = [];
        foreach ($items->whereNotNull('agendable_type')->groupBy('agendable_type') as $type => $group) {
            if (! class_exists($type)) {
                continue;
            }
            try {
                $models[$type] = $type::whereKey($group->pluck('agendable_id')->unique()->all())->get()->keyBy(fn ($m) => $m->getKey());
            } catch (\Throwable) {
                // Modul ohne Tabelle o. ä. — Karten bleiben unverknüpft
            }
        }

        return $items->map(function (AgendaItem $item) use ($models) {
            $model = $item->agendable_type ? ($models[$item->agendable_type][$item->agendable_id] ?? null) : null;
            $rendered = [];
            if ($model instanceof AgendaRenderable) {
                try {
                    $rendered = $model->toAgendaItem();
                } catch (\Throwable) {
                    $rendered = [];
                }
            }

            return [
                'id' => $item->id,
                'slot_id' => $item->agenda_slot_id,
                'title' => $item->title,
                'notes' => $item->notes,
                'date' => $item->date?->toDateString(),
                'date_label' => $item->date?->translatedFormat('D, d. M'),
                'is_overdue' => $item->date && ! $item->is_done && $item->date->isBefore(today()),
                'time_start' => $item->time_start ? substr($item->time_start, 0, 5) : null,
                'time_end' => $item->time_end ? substr($item->time_end, 0, 5) : null,
                'is_done' => (bool) $item->is_done,
                'color' => $item->color,
                'is_linked' => (bool) $item->agendable_type,
                'type_label' => $this->typeLabel($item->agendable_type),
                'missing' => $item->agendable_type && ! $model,
                'status' => $rendered['status'] ?? null,
                'status_color' => $rendered['status_color'] ?? null,
                'description' => $item->notes ?: ($rendered['description'] ?? null),
                'url' => $rendered['url'] ?? null,
                'due_date' => $rendered['meta']['due_date'] ?? null,
            ];
        })->values()->all();
    }

    protected function titleOf(Model $model): ?string
    {
        if ($model instanceof HasDisplayName && ($name = $model->getDisplayName())) {
            return $name;
        }

        return $model->title ?? $model->name ?? null;
    }
}
