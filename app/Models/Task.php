<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    public const TODO = 'todo';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';

    public const STATUSES = [
        self::TODO => 'To do',
        self::IN_PROGRESS => 'In progress',
        self::COMPLETED => 'Completed',
    ];

    protected $fillable = ['team_id', 'title', 'description', 'status', 'assignee_id', 'created_by', 'due_date'];

    protected $casts = ['due_date' => 'date'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Label + CSS modifier for the badge on a task card. */
    public function badge(): array
    {
        if ($this->status === self::COMPLETED) {
            return ['Done', 'done'];
        }
        if ($this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday()) {
            return ['Overdue', 'late'];
        }
        if ($this->status === self::IN_PROGRESS) {
            return ['In progress', 'progress'];
        }
        if ($this->due_date && $this->due_date->lte(now()->addDays(3))) {
            return ['Due soon', 'soon'];
        }

        return ['To do', 'todo'];
    }
}
