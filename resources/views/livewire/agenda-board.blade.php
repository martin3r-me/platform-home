{{-- Agenda-Board: Backlog · eigene Spalten · Erledigt — Stil wie die Planner-Boards --}}
@php
    $agenda = $this->agenda;
    $isOwner = $this->isOwner;
    $openCount = count($backlog['items'] ?? []) + collect($slotColumns)->sum(fn ($c) => count($c['items']));
    $fieldClass = 'w-full text-sm border border-[color:var(--nx-line-strong)] rounded-[6px] px-2 py-1.5 bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)] outline-none';
    $inputClass = 'w-full text-xs border border-[color:var(--nx-line-strong)] rounded-[6px] px-2 py-1.5 bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)] outline-none';
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$agenda->name" icon="heroicon-o-queue-list" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Agenda', 'route' => 'home.agenda', 'icon' => 'queue-list'],
            ['label' => $agenda->name],
        ]">
            <x-nx-badge title="offen {{ $openCount }} · erledigt {{ count($done['items'] ?? []) }}">
                <span class="inline-flex items-center gap-1 text-[color:var(--nx-text)]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[color:var(--nx-info)]"></span>
                    <span class="tabular-nums">{{ $openCount }}</span>
                </span>
                <span class="inline-flex items-center gap-1 text-[color:var(--nx-text)]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[color:var(--nx-success)]"></span>
                    <span class="tabular-nums">{{ count($done['items'] ?? []) }}</span>
                </span>
                @if(count($this->members) > 1)
                    <span class="opacity-30" aria-hidden="true">·</span>
                    <span class="inline-flex items-center gap-1">@svg('heroicon-o-users', 'h-3 w-3') <span class="tabular-nums">{{ count($this->members) }}</span></span>
                @endif
            </x-nx-badge>

            <x-nx-dropdown>
                @if($isOwner)
                    <x-nx-dropdown-item x-on:click="open = false; $dispatch('agenda-new-slot')">
                        @svg('heroicon-o-square-2-stack', 'w-4 h-4 text-[color:var(--nx-muted)]') Neue Spalte
                    </x-nx-dropdown-item>
                @endif
                <x-nx-dropdown-item wire:click="openSettings" x-on:click="open = false">
                    @svg('heroicon-o-users', 'w-4 h-4 text-[color:var(--nx-muted)]') {{ $isOwner ? 'Einstellungen & Teilen' : 'Mitglieder' }}
                </x-nx-dropdown-item>
                <x-nx-dropdown-divider />
                @if($isOwner)
                    <x-nx-dropdown-item variant="danger" wire:click="deleteAgenda" wire:confirm="Agenda „{{ $agenda->name }}“ mit allen Karten löschen?">
                        @svg('heroicon-o-trash', 'w-4 h-4') Agenda löschen
                    </x-nx-dropdown-item>
                @else
                    <x-nx-dropdown-item variant="danger" wire:click="leave" wire:confirm="Diese Agenda verlassen?">
                        @svg('heroicon-o-arrow-right-on-rectangle', 'w-4 h-4') Agenda verlassen
                    </x-nx-dropdown-item>
                @endif
            </x-nx-dropdown>
        </x-ui-page-actionbar>
    </x-slot>

    <div class="flex-1 min-w-0 min-h-0 flex flex-col overflow-hidden bg-[color:var(--nx-surface)]">
        <x-nx-kanban-container sortable="updateSlotOrder" sortable-group="updateItemOrder">

            {{-- Spalten: Backlog (fest) + eigene (verschiebbar) --}}
            @foreach(array_merge([$backlog], $slotColumns) as $column)
                @php $isBacklog = $column['key'] === 'backlog'; @endphp
                <x-nx-kanban-column
                    :title="$column['name']"
                    :sortable-id="$isBacklog || ! $isOwner ? null : $column['id']"
                    :item-group="$column['key']"
                    :tone="$column['tone']"
                    :muted="$isBacklog"
                    :count="count($column['items'])"
                    wire:key="agenda-col-{{ $column['key'] }}">

                    @if(! $isBacklog && $isOwner)
                        <x-slot name="headerActions">
                            <div x-data="{ menu: false }" class="relative">
                                <button type="button" @click="menu = !menu" class="text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)] transition-colors" title="Spalte">
                                    @svg('heroicon-o-ellipsis-horizontal', 'w-4 h-4')
                                </button>
                                <div x-show="menu" x-cloak @click.outside="menu = false"
                                     class="absolute right-0 top-full z-30 mt-1 w-52 rounded-lg border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] p-2 shadow-[var(--nx-shadow-pop)] space-y-2">
                                    <input type="text" value="{{ $column['name'] }}" class="{{ $inputClass }}"
                                           @keydown.enter.prevent="$wire.renameSlot({{ $column['id'] }}, $event.target.value); menu = false"
                                           title="Name — Enter speichert">
                                    <div class="flex flex-wrap gap-1.5 px-0.5">
                                        @foreach($tones as $tone)
                                            <button type="button" wire:click="setSlotTone({{ $column['id'] }}, '{{ $tone }}')" @click="menu = false"
                                                    class="h-4 w-4 rounded-full {{ $column['tone'] === $tone ? 'ring-2 ring-offset-1 ring-[color:var(--nx-line-strong)]' : '' }}"
                                                    style="background-color: var(--nx-tone-{{ $tone }});" title="{{ $tone }}"></button>
                                        @endforeach
                                    </div>
                                    <button type="button" wire:click="deleteSlot({{ $column['id'] }})" wire:confirm="Spalte löschen? Die Karten wandern in den Backlog."
                                            class="flex w-full items-center gap-1.5 rounded-[6px] px-1.5 py-1 text-xs text-[color:var(--nx-danger)] hover:bg-[rgba(224,49,49,.08)]">
                                        @svg('heroicon-o-trash', 'w-3.5 h-3.5') Spalte löschen
                                    </button>
                                </div>
                            </div>
                        </x-slot>
                    @endif

                    @forelse($column['items'] as $item)
                        @include('home::partials.agenda-card', ['item' => $item])
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-[color:var(--nx-muted)]">
                            @svg($isBacklog ? 'heroicon-o-inbox' : 'heroicon-o-clipboard', 'w-8 h-8 mb-2 opacity-40')
                            <span class="text-xs">{{ $isBacklog ? 'Backlog ist leer' : 'Keine Karten' }}</span>
                            <span class="text-[10px] mt-0.5 opacity-60">{{ $isBacklog ? 'Über das Kontext-Panel „Agenda“ landen Dinge hier' : 'Hierher ziehen oder neu anlegen' }}</span>
                        </div>
                    @endforelse

                    <x-slot name="footer">
                        <div x-data="{ open: false, title: '' }">
                            <button x-show="!open" @click="open = true; $nextTick(() => $refs.input.focus())"
                                    class="w-full text-left text-xs text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)] transition-colors flex items-center gap-1.5 px-2 py-1">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') <span>Karte</span>
                            </button>
                            <div x-show="open" x-cloak>
                                <input x-ref="input" x-model="title" type="text" placeholder="Titel eingeben…" class="{{ $inputClass }}"
                                       @keydown.enter.prevent="if (title.trim()) { $wire.createItem('{{ $column['key'] }}', title.trim()); title = ''; }"
                                       @keydown.escape="open = false; title = ''"
                                       @click.outside="open = false; title = ''">
                            </div>
                        </div>
                    </x-slot>
                </x-nx-kanban-column>
            @endforeach

            {{-- Neue Spalte --}}
            @if($isOwner)
                <div class="flex-shrink-0 pt-1.5" style="width: 19rem; min-width: 19rem;"
                     x-data="{ open: false, name: '' }"
                     @agenda-new-slot.window="open = true; $nextTick(() => $refs.slot.focus())">
                    <button x-show="!open" @click="open = true; $nextTick(() => $refs.slot.focus())"
                            class="flex items-center gap-1.5 rounded-[8px] px-3 py-2 text-xs text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)] transition-colors">
                        @svg('heroicon-o-plus', 'w-4 h-4') Spalte hinzufügen
                    </button>
                    <div x-show="open" x-cloak class="rounded-[10px] bg-[color:var(--nx-bg)] p-2">
                        <input x-ref="slot" x-model="name" type="text" placeholder="Spalten-Name…" class="{{ $inputClass }}"
                               @keydown.enter.prevent="if (name.trim()) { $wire.createSlot(name.trim()); name = ''; open = false; }"
                               @keydown.escape="open = false; name = ''"
                               @click.outside="open = false; name = ''">
                    </div>
                </div>
            @endif

            {{-- Erledigt: aufgeklappt als Spalte, sonst schmaler Streifen --}}
            @if($done)
                @if($showDone)
                    <x-nx-kanban-column :title="$done['name']" item-group="done" :muted="true" tone="emerald" :count="count($done['items'])" wire:key="agenda-col-done">
                        <x-slot name="headerActions">
                            <button type="button" wire:click="$set('showDone', false)" class="text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)] transition-colors" title="Einklappen">
                                @svg('heroicon-o-chevron-double-right', 'w-4 h-4')
                            </button>
                        </x-slot>
                        @forelse($done['items'] as $item)
                            @include('home::partials.agenda-card', ['item' => $item])
                        @empty
                            <div class="flex flex-col items-center justify-center py-8 text-[color:var(--nx-muted)]">
                                @svg('heroicon-o-check-circle', 'w-8 h-8 mb-2 opacity-40')
                                <span class="text-xs">Noch nichts erledigt</span>
                            </div>
                        @endforelse
                    </x-nx-kanban-column>
                @else
                    <button type="button" wire:click="$set('showDone', true)" wire:key="agenda-col-done-collapsed"
                            class="sticky right-0 z-10 flex h-full flex-shrink-0 flex-col items-center justify-between border-l border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-2 py-4 transition-colors hover:bg-[color:var(--nx-hover)]"
                            style="width: 2.75rem; min-width: 2.75rem;"
                            title="Erledigte anzeigen ({{ count($done['items']) }})">
                        @svg('heroicon-o-chevron-double-left', 'w-4 h-4 text-[color:var(--nx-success)] mt-1')
                        <span class="my-2 flex-1 text-[10px] font-bold uppercase tracking-wider text-[color:var(--nx-success)]" style="writing-mode: vertical-rl; transform: rotate(180deg);">Erledigt</span>
                        <span class="inline-flex h-5 min-w-[1.5rem] items-center justify-center rounded-full bg-[rgba(47,158,68,0.16)] px-1 text-[10px] font-semibold tabular-nums text-[color:var(--nx-success)]">{{ count($done['items']) }}</span>
                    </button>
                @endif
            @endif
        </x-nx-kanban-container>
    </div>

    {{-- Karte bearbeiten --}}
    <x-nx-modal wire:model="showItem" size="md">
        <x-slot name="header">
            <div class="text-sm font-semibold">Karte</div>
        </x-slot>
        <div class="space-y-4">
            <label class="block">
                <span class="text-xs font-medium text-[color:var(--nx-muted)]">Titel</span>
                <input type="text" wire:model="editTitle" maxlength="255" class="mt-1 {{ $fieldClass }}">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-[color:var(--nx-muted)]">Notiz</span>
                <textarea wire:model="editNotes" rows="4" class="mt-1 {{ $fieldClass }}"></textarea>
            </label>
            <label class="block">
                <span class="text-xs font-medium text-[color:var(--nx-muted)]">Datum</span>
                <input type="date" wire:model="editDate" class="mt-1 {{ $fieldClass }} !w-auto">
                @error('editDate') <span class="mt-1 block text-xs text-[color:var(--nx-danger)]">{{ $message }}</span> @enderror
            </label>
        </div>
        <x-slot name="footer">
            @if($editId)
                <x-nx-button variant="danger" wire:click="deleteItem({{ $editId }})" class="mr-auto">
                    @svg('heroicon-o-trash', 'w-4 h-4') Von der Agenda nehmen
                </x-nx-button>
            @endif
            <x-nx-button x-on:click="open = false">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="saveItem">Speichern</x-nx-button>
        </x-slot>
    </x-nx-modal>

    {{-- Einstellungen & Teilen --}}
    <x-nx-modal wire:model="showSettings" size="md">
        <x-slot name="header">
            <div class="text-sm font-semibold">{{ $isOwner ? 'Einstellungen & Teilen' : 'Mitglieder' }}</div>
        </x-slot>
        <div class="space-y-5">
            @if($isOwner)
                <label class="block">
                    <span class="text-xs font-medium text-[color:var(--nx-muted)]">Name</span>
                    <input type="text" wire:model="settingsName" maxlength="120" class="mt-1 {{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="text-xs font-medium text-[color:var(--nx-muted)]">Beschreibung</span>
                    <textarea wire:model="settingsDescription" rows="2" class="mt-1 {{ $fieldClass }}"></textarea>
                </label>
            @endif

            <div>
                <div class="mb-1.5 text-xs font-medium text-[color:var(--nx-muted)]">Mitglieder</div>
                <ul class="divide-y divide-[color:var(--nx-line)] rounded-[8px] border border-[color:var(--nx-line)]">
                    @foreach($this->members as $member)
                        <li class="flex items-center gap-2 px-3 py-2 text-sm" wire:key="agenda-member-{{ $member['id'] }}">
                            @if($member['avatar'])
                                <img src="{{ $member['avatar'] }}" alt="" class="h-5 w-5 rounded-[4px] object-cover">
                            @else
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-[4px] bg-[color:var(--nx-accent-soft)] text-[9px] font-semibold">{{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}</span>
                            @endif
                            <span class="min-w-0 flex-1 truncate">{{ $member['name'] }}</span>
                            <span class="text-xs text-[color:var(--nx-faint)]">{{ $member['role'] === 'owner' ? 'Besitzer' : 'Mitglied' }}</span>
                            @if($isOwner && $member['role'] !== 'owner')
                                <button type="button" wire:click="removeMember({{ $member['id'] }})" class="rounded p-0.5 text-[color:var(--nx-faint)] hover:text-[color:var(--nx-danger)]" title="Entfernen">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if($isOwner && count($this->candidates))
                    <select class="mt-2 {{ $fieldClass }}" x-on:change="if ($event.target.value) { $wire.addMember(parseInt($event.target.value)); $event.target.value = ''; }">
                        <option value="">Person aus dem Team hinzufügen …</option>
                        @foreach($this->candidates as $candidate)
                            <option value="{{ $candidate['id'] }}">{{ $candidate['name'] }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
        <x-slot name="footer">
            <x-nx-button x-on:click="open = false">{{ $isOwner ? 'Abbrechen' : 'Schließen' }}</x-nx-button>
            @if($isOwner)
                <x-nx-button variant="primary" wire:click="saveSettings">Speichern</x-nx-button>
            @endif
        </x-slot>
    </x-nx-modal>
</x-ui-page>
