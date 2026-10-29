{{-- Widget: Offene To-dos (Core Check-in). Direkt abhakbar. --}}
<x-home::widget title="Offene To-dos" icon="heroicon-o-clipboard-document-check"
    :badge="count($openTodos) > 0 ? (string) count($openTodos) : null">

    @if(empty($openTodos))
        <div class="px-4 py-6"><x-nx-empty icon="heroicon-o-check-circle">Keine offenen To-dos — alles erledigt.</x-nx-empty></div>
    @else
        <ul class="divide-y divide-[color:var(--nx-line)]">
            @foreach($openTodos as $todo)
                <li>
                    <button type="button" wire:click="toggleTodo({{ $todo['id'] }})" wire:loading.attr="disabled"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-[color:var(--nx-hover)]"
                            aria-label="Als erledigt markieren">
                        <span class="h-4 w-4 shrink-0 rounded-[5px] border border-[color:var(--nx-line-strong,var(--nx-line))]"></span>
                        <span class="min-w-0 flex-1 text-sm text-[color:var(--nx-text)]">{{ $todo['title'] }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</x-home::widget>
