<?php

namespace Platform\Home\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class Agenda extends Model
{
    protected $table = 'terminal_agendas';

    protected $fillable = [
        'uuid',
        'team_id',
        'name',
        'description',
        'icon',
        'item_count',
        'created_by_user_id',
    ];

    protected $casts = [
        'item_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }

            if (empty($model->created_by_user_id) && auth()->check()) {
                $model->created_by_user_id = auth()->id();
            }
        });
    }

    // ── Relations ──────────────────────────────────────────────

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AgendaMember::class, 'agenda_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AgendaItem::class, 'agenda_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AgendaSlot::class, 'agenda_id');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function refreshItemCount(): void
    {
        $this->update(['item_count' => $this->items()->where('is_done', false)->count()]);
    }
}
