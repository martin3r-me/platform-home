<?php

namespace Platform\Home\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Home\Models\Agenda;
use Platform\Home\Models\AgendaMember;
use Platform\Home\Models\AgendaSlot;

class ManageAgendaSlotsTool implements ToolContract
{
    public function getName(): string
    {
        return 'home.agenda_slots.POST';
    }

    public function getDescription(): string
    {
        return 'Verwaltet Kanban-Slots (Spalten) einer Agenda. '
            . 'Aktionen: "create" (neuen Slot erstellen), "rename" (Slot umbenennen), "delete" (Slot löschen, Items → Backlog), "reorder" (Reihenfolge ändern).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'agenda_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Agenda.',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['create', 'rename', 'delete', 'reorder'],
                    'description' => 'Aktion.',
                ],
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Slots (erforderlich bei rename/delete).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Slots (erforderlich bei create/rename).',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Farbe des Slots.',
                ],
                'order' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Slot-IDs in gewünschter Reihenfolge (erforderlich bei reorder).',
                ],
            ],
            'required' => ['agenda_id', 'action'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        $user = $context->user;
        $team = $context->team;

        if (! $user || ! $team) {
            return ToolResult::error('User und Team-Kontext erforderlich.', 'NO_CONTEXT');
        }

        $agendaId = $arguments['agenda_id'] ?? null;
        $action = $arguments['action'] ?? null;

        if (! $agendaId || ! $action) {
            return ToolResult::error('agenda_id und action sind erforderlich.', 'VALIDATION_ERROR');
        }

        $agenda = Agenda::where('id', $agendaId)
            ->where('team_id', $team->id)
            ->first();

        if (! $agenda) {
            return ToolResult::error('Agenda nicht gefunden.', 'NOT_FOUND');
        }

        $isMember = AgendaMember::where('agenda_id', $agenda->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            return ToolResult::error('Du bist kein Mitglied dieser Agenda.', 'FORBIDDEN');
        }

        return match ($action) {
            'create' => $this->createSlot($arguments, $agenda),
            'rename' => $this->renameSlot($arguments, $agenda),
            'delete' => $this->deleteSlot($arguments, $agenda),
            'reorder' => $this->reorderSlots($arguments, $agenda),
            default => ToolResult::error('Ungültige action. Erlaubt: create, rename, delete, reorder.', 'VALIDATION_ERROR'),
        };
    }

    private function createSlot(array $arguments, Agenda $agenda): ToolResult
    {
        $name = trim($arguments['name'] ?? '');
        if (empty($name)) {
            return ToolResult::error('name ist erforderlich bei action=create.', 'VALIDATION_ERROR');
        }

        $maxOrder = AgendaSlot::where('agenda_id', $agenda->id)->max('order') ?? 0;

        $slot = AgendaSlot::create([
            'agenda_id' => $agenda->id,
            'name' => $name,
            'order' => $maxOrder + 1,
            'color' => $arguments['color'] ?? null,
        ]);

        return ToolResult::success([
            'slot_id' => $slot->id,
            'agenda_id' => $agenda->id,
            'name' => $slot->name,
            'action' => 'created',
        ]);
    }

    private function renameSlot(array $arguments, Agenda $agenda): ToolResult
    {
        $slotId = $arguments['slot_id'] ?? null;
        $name = trim($arguments['name'] ?? '');

        if (! $slotId || empty($name)) {
            return ToolResult::error('slot_id und name sind erforderlich bei action=rename.', 'VALIDATION_ERROR');
        }

        $slot = AgendaSlot::where('id', $slotId)
            ->where('agenda_id', $agenda->id)
            ->first();

        if (! $slot) {
            return ToolResult::error('Slot nicht gefunden.', 'NOT_FOUND');
        }

        $slot->update(['name' => $name, 'color' => array_key_exists('color', $arguments) ? $arguments['color'] : $slot->color]);

        return ToolResult::success([
            'slot_id' => $slot->id,
            'name' => $slot->name,
            'action' => 'renamed',
        ]);
    }

    private function deleteSlot(array $arguments, Agenda $agenda): ToolResult
    {
        $slotId = $arguments['slot_id'] ?? null;
        if (! $slotId) {
            return ToolResult::error('slot_id ist erforderlich bei action=delete.', 'VALIDATION_ERROR');
        }

        $slot = AgendaSlot::where('id', $slotId)
            ->where('agenda_id', $agenda->id)
            ->first();

        if (! $slot) {
            return ToolResult::error('Slot nicht gefunden.', 'NOT_FOUND');
        }

        // Move items to backlog (null slot)
        $slot->items()->update(['agenda_slot_id' => null]);
        $slot->delete();

        return ToolResult::success([
            'deleted_slot_id' => $slotId,
            'agenda_id' => $agenda->id,
            'action' => 'deleted',
        ]);
    }

    private function reorderSlots(array $arguments, Agenda $agenda): ToolResult
    {
        $order = $arguments['order'] ?? [];
        if (empty($order)) {
            return ToolResult::error('order ist erforderlich bei action=reorder.', 'VALIDATION_ERROR');
        }

        foreach ($order as $index => $slotId) {
            AgendaSlot::where('id', $slotId)
                ->where('agenda_id', $agenda->id)
                ->update(['order' => $index]);
        }

        return ToolResult::success([
            'agenda_id' => $agenda->id,
            'action' => 'reordered',
            'slot_count' => count($order),
        ]);
    }
}
