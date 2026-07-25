{{-- System: Ereignis + Aktion --}}
<div class="space-y-4">
    <div class="whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-text)]">{{ $item['body'] ?? '' }}</div>
    <div class="flex flex-wrap gap-2">
        <x-nx-button variant="primary" size="sm">{{ $item['action_label'] ?? 'Öffnen' }}</x-nx-button>
    </div>
</div>
