<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disc extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'occurred_at',
        'manufacturer',
        'model_name',
        'plastic_type',
        'back_text',
        'condition_estimate',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Location>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * @return BelongsToMany<Color>
     */
    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'disc_colors');
    }
}
