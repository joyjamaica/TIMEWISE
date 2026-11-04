<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = ['name', 'description', 'visibility', 'invite_code', 'owner_id'];

    public static function generateInviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
         return $this->hasMany(TeamTask::class, 'team_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(TeamResource::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('memberships', fn ($q) => $q->where('user_id', $user->id));
    }

    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->exists();
    }

    /** Percentage of tasks in the "completed" status (0 when there are no tasks). */
    public function getProgressAttribute(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        if ($total === 0) {
            return 0;
        }
        $done = $this->completed_tasks_count
            ?? $this->tasks()->where('status', TeamTask::COMPLETED)->count();

        return (int) round($done / $total * 100);
    }
}
