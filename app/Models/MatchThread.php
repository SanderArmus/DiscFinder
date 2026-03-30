<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MatchThread extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'lost_disc_id',
        'found_disc_id',
        'match_score',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_score' => 'float',
        ];
    }

    public function lostDisc(): BelongsTo
    {
        return $this->belongsTo(Disc::class, 'lost_disc_id');
    }

    public function foundDisc(): BelongsTo
    {
        return $this->belongsTo(Disc::class, 'found_disc_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'match_id');
    }

    public function confirmation(): HasOne
    {
        return $this->hasOne(Confirmation::class, 'match_id');
    }
}
