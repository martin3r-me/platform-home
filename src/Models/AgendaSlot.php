<?php

namespace Platform\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaSlot extends Model
{
    protected $table = 'terminal_agenda_slots';

    protected $fillable = [
        'agenda_id',
        'name',
        'order',
        'color',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AgendaItem::class, 'agenda_slot_id');
    }
}
