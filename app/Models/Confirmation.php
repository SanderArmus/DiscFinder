<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Confirmation extends Model
{
    protected $fillable = [
        'match_id',
        'owner_confirmed',
        'finder_confirmed',
        'confirmed_at',
        'owner_handed_over',
        'finder_handed_over',
        'handed_over_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_confirmed' => 'boolean',
            'finder_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
            'owner_handed_over' => 'boolean',
            'finder_handed_over' => 'boolean',
            'handed_over_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchThread::class, 'match_id');
    }
}
