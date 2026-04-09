<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatReport extends Model
{
    protected $fillable = [
        'reporter_id',
        'reported_id',
        'match_id',
        'reason',
        'details',
        'last_message_preview',
        'last_message_at',
        'messages_snapshot',
    ];

    /**
     * @return BelongsTo<User, ChatReport>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<User, ChatReport>
     */
    public function reported(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_id');
    }

    /**
     * @return BelongsTo<MatchThread, ChatReport>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchThread::class, 'match_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'messages_snapshot' => 'array',
        ];
    }
}
