<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatBlock extends Model
{
    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'match_id',
    ];

    /**
     * @return BelongsTo<User, ChatBlock>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * @return BelongsTo<User, ChatBlock>
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    /**
     * @return BelongsTo<MatchThread, ChatBlock>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchThread::class, 'match_id');
    }
}
