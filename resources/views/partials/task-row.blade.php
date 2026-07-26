{{--
    Eine Aufgaben-Zeile in „Meine Aufgaben".
    Erwartet: $t (array aus PersonTaskSummary::openForUser).
--}}
<li>
    <x-nx-list-item
        :icon="$t['is_frog'] ? 'heroicon-o-bolt' : 'heroicon-o-clipboard-document-check'"
        :title="$t['title']"
        :subtitle="$t['project']"
        :meta="$t['due_label']">
        @if($t['is_frog'] || ($t['priority'] ?? null) === 'high' || $t['is_overdue'])
            <x-slot name="trailing">
                <div class="flex items-center gap-1.5">
                    @if(($t['priority'] ?? null) === 'high')
                        <x-nx-badge variant="warning">Hoch</x-nx-badge>
                    @endif
                    @if($t['is_frog'])
                        <x-nx-badge variant="accent">Frog</x-nx-badge>
                    @endif
                </div>
            </x-slot>
        @endif
    </x-nx-list-item>
</li>
