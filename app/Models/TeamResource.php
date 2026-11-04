<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamResource extends Model
{
    protected $fillable = ['team_id', 'uploaded_by', 'title', 'path', 'extension', 'size'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
