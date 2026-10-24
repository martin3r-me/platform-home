<?php

namespace Platform\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\Core\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaMember extends Model
{
    protected $table = 'terminal_agenda_members';

    protected $fillable = [
        'agenda_id',
        'user_id',
        'role',
    ];

    // ── Relations ──────────────────────────────────────────────

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
