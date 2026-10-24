<?php

namespace Platform\Home\Terminal;

use Platform\Core\Terminal\Apps\ContextBadges;
use Platform\Core\Terminal\Contracts\HasBadge;
use Platform\Core\Terminal\Contracts\TerminalApp;
use Platform\Core\Terminal\TerminalContext;
use Platform\Home\Services\AgendaService;

/**
 * Reiter „Agenda“ im Kontext-Panel: den aktuellen Datensatz auf eigene Agenden
 * legen. Badge = auf wie vielen meiner Agenden er liegt.
 */
class AgendaPanelApp implements TerminalApp, HasBadge
{
    public function key(): string
    {
        return 'agenda';
    }

    public function label(): string
    {
        return 'Agenda';
    }

    public function icon(): string
    {
        return 'queue-list';
    }

    public function group(): string
    {
        return 'module';
    }

    public function order(): int
    {
        return 50;
    }

    public function livewireComponent(): string
    {
        return 'home.panel.agenda';
    }

    public function isAvailable(TerminalContext $context): bool
    {
        return $context->hasContext() && $context->offers('agenda');
    }

    public function badge(TerminalContext $context): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        $service = app(AgendaService::class);

        return ContextBadges::count(count(
            $service->agendaIdsContaining($context->contextType, $context->contextId, $user->id, $service->teamIdFor($user))
        ));
    }
}
