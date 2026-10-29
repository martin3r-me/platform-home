{{-- Undo-Toast: erscheint nach einer Triage (Erledigt/Ignoriert/Wiedervorlage),
     bietet „Rückgängig" (wire:click undoTriage → reopen). Hört auf die vom
     Inbox-Component gefeuerten Livewire-Browser-Events; auto-hide nach 6s. --}}
<div x-data="{ show: false, label: '', undoable: true, timer: null }"
     x-on:inbox-triaged.window="label = ($event.detail && $event.detail.label) ? $event.detail.label : 'Erledigt'; undoable = !($event.detail && $event.detail.undoable === false); show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 6000)"
     x-on:inbox-undone.window="show = false; clearTimeout(timer)"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="translate-y-2 opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-x-0 bottom-5 z-50 mx-auto flex w-max max-w-[90vw] items-center gap-3 rounded-full border border-[color:var(--nx-line)] bg-[color:var(--nx-text)] py-2 pl-4 pr-2 text-sm shadow-lg"
     style="color: var(--nx-on-accent, var(--nx-surface));">
    <span class="flex items-center gap-1.5">
        @svg('heroicon-o-check-circle', 'h-4 w-4 shrink-0')
        <span x-text="label"></span>
    </span>
    <button type="button" x-show="undoable" wire:click="undoTriage" @click="show = false; clearTimeout(timer)"
            class="rounded-full px-3 py-1 text-sm font-semibold transition-colors hover:bg-white/15">
        Rückgängig
    </button>
</div>
