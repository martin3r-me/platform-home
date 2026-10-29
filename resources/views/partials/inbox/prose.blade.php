{{-- Lese-Typografie für Freitext-Bodies — EINHEITLICH über alle Eingangstypen.
     Begrenzte Zeilenbreite (Lesbarkeit), 15px/leading-relaxed, bricht lange
     Tokens (URLs) statt zu overflowen. $text = der reine Text. --}}
@php($text = trim((string) ($text ?? '')))
@if($text !== '')
    <div class="max-w-[68ch] whitespace-pre-line text-[15px] leading-relaxed text-[color:var(--nx-text)] [overflow-wrap:anywhere]">{{ $text }}</div>
@endif
