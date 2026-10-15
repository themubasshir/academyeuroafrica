<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['live_session_id', 'user_id', 'attended', 'minutes', 'marked_by', 'marked_at'])]
class SessionAttendance extends Model
{
    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'live_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
            'marked_at' => 'datetime',
        ];
    }
}
