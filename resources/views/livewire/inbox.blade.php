<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Eingang" icon="heroicon-o-inbox" />
    </x-slot>

    {{-- Links: Kanäle & Filter --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Kanäle" icon="heroicon-o-inbox" width="w-64" :defaultOpen="true" side="left">
            <div class="p-3">
                <div class="px-2 pb-1 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">Kanäle</div>
                <div class="flex flex-col gap-0.5">
                    @foreach($channels as $c)
                        <button type="button" wire:click="setChannel('{{ $c['key'] }}')"
                                class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left transition-colors {{ $channel === $c['key'] ? 'bg-[color:var(--nx-active)] font-semibold' : 'hover:bg-[color:var(--nx-hover)]' }}">
                            @svg($c['icon'], 'h-4 w-4 shrink-0 text-[color:var(--nx-muted)]')
                            <span class="flex-1 truncate text-sm text-[color:var(--nx-text)]">{{ $c['label'] }}</span>
                            @if($c['count'])
                                <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $c['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Rechts: Item-Liste --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Eingang" icon="heroicon-o-inbox" width="w-80" :defaultOpen="true" side="right" storeKey="activityOpen">
            {{-- Status-Achse (kombiniert mit dem Kanal links) --}}
            <div class="flex flex-wrap gap-1 border-b border-[color:var(--nx-line)] px-3 py-2">
                @foreach($statuses as $s)
                    <button type="button" wire:click="setStatus('{{ $s['key'] }}')"
                            class="rounded-md px-2 py-1 text-xs font-medium transition-colors {{ $status === $s['key'] ? 'bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">
                        {{ $s['label'] }}
                    </button>
                @endforeach
            </div>

            @if(empty($items))
                <x-nx-empty icon="heroicon-o-check-circle">Nichts hier — sauber.</x-nx-empty>
            @else
                <ul class="divide-y divide-[color:var(--nx-line)]">
                    @php $prevSection = null; @endphp
                    @foreach($items as $it)
                        @if(!empty($it['section']) && $it['section'] !== $prevSection)
                            @php $prevSection = $it['section']; @endphp
                            <li class="bg-[color:var(--nx-bg-soft,transparent)] px-4 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-wide text-[color:var(--nx-faint)]">
                                {{ $it['section'] === 'upcoming' ? 'Anstehend' : 'Vergangen' }}
                            </li>
                        @endif
                        <li>
                            <button type="button" wire:click="selectItem({{ $it['id'] }})"
                                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors {{ $selected && $selected['id'] === $it['id'] ? 'bg-[color:var(--nx-active)]' : 'hover:bg-[color:var(--nx-hover)]' }}">
                                @svg($it['icon'], 'mt-0.5 h-4 w-4 shrink-0 text-[color:var(--nx-muted)]')
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm text-[color:var(--nx-text)] {{ $it['unread'] ? 'font-semibold' : '' }}">{{ $it['sender'] }}</span>
                                        <span class="shrink-0 text-[10px] tabular-nums text-[color:var(--nx-faint)]">{{ $it['time'] }}</span>
                                    </div>
                                    <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $it['subject'] }}</div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        @if(!empty($it['is_series']))
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[color:var(--nx-accent-soft)] px-1.5 py-0.5 text-[10px] font-medium text-[color:var(--nx-muted)]">
                                                @svg('heroicon-o-arrow-path', 'h-3 w-3')
                                                Serie{{ ($it['series_count'] ?? 1) > 1 ? ' · ' . $it['series_count'] : '' }}
                                            </span>
                                        @endif
                                        <span class="truncate text-xs text-[color:var(--nx-faint)]">{{ $it['preview'] }}</span>
                                    </div>
                                </div>
                                @if($it['unread'])
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[color:var(--nx-accent)]"></span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Mitte: Reading-Pane --}}
    <x-ui-page-container width="contained">
        @if($selected)
            <div class="space-y-5">
                <div>
                    <div class="flex items-center gap-2 text-xs text-[color:var(--nx-faint)]">
                        <x-nx-badge variant="neutral">{{ $selected['channel_label'] }}</x-nx-badge>
                        <span>{{ $selected['time'] }}</span>
                    </div>
                    <h1 class="mt-2 text-xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $selected['subject'] }}</h1>
                    <div class="mt-1 text-sm text-[color:var(--nx-muted)]">{{ $selected['sender'] }}</div>
                </div>

                {{-- Action-Bar: triagieren & daraus Arbeit machen (kein Antworten) --}}
                <div class="flex flex-wrap gap-2">
                    <x-nx-button variant="primary" size="sm">Erledigt</x-nx-button>
                    <x-nx-button variant="secondary" size="sm">Snooze</x-nx-button>
                    <x-nx-button variant="secondary" size="sm">→ Aufgabe</x-nx-button>
                    <x-nx-button variant="secondary" size="sm">→ Ticket</x-nx-button>
                </div>

                @if(!empty($selected['summary']))
                    <x-nx-callout variant="info" title="KI-Zusammenfassung">
                        {{ $selected['summary'] }}
                    </x-nx-callout>
                @endif

                {{-- Org-Kontext: wo hängt's, was noch dran — der eigentliche Kern --}}
                <x-nx-card>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-[color:var(--nx-faint)]">
                            @svg('heroicon-o-share', 'w-4 h-4')
                            Kontext
                        </div>
                        <x-nx-button variant="ghost" size="sm">An Knoten hängen</x-nx-button>
                    </div>
                    @if(!empty($selected['context']))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($selected['context'] as $ctx)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[color:var(--nx-accent-soft)] px-2 py-1 text-xs text-[color:var(--nx-text)]">
                                    @svg($ctx['icon'], 'h-3.5 w-3.5 text-[color:var(--nx-muted)]')
                                    {{ $ctx['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    @if($selected['related'])
                        <div class="mt-2 text-xs text-[color:var(--nx-faint)]">Am Knoten: {{ $selected['related'] }}</div>
                    @endif
                </x-nx-card>

                {{-- Kanal-spezifischer Body --}}
                @switch($selected['channel'])
                    @case('mail')
                        @include('home::partials.inbox.body-mail', ['item' => $selected])
                    @break
                    @case('message')
                        @include('home::partials.inbox.body-message', ['item' => $selected])
                    @break
                    @case('meeting')
                        @include('home::partials.inbox.body-meeting', ['item' => $selected])
                    @break
                    @case('task')
                        @include('home::partials.inbox.body-task', ['item' => $selected])
                    @break
                    @case('call')
                        @include('home::partials.inbox.body-call', ['item' => $selected])
                    @break
                    @case('recording')
                        @include('home::partials.inbox.body-recording', ['item' => $selected])
                    @break
                    @default
                        @include('home::partials.inbox.body-system', ['item' => $selected])
                @endswitch
            </div>
        @else
            <x-nx-empty icon="heroicon-o-inbox">
                Wähle rechts einen Eingang — hier erscheint der Inhalt.
            </x-nx-empty>
        @endif
    </x-ui-page-container>
</x-ui-page>
