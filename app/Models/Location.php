<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'disc_id',
        'latitude',
        'longitude',
        'location_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function disc(): BelongsTo
    {
        return $this->belongsTo(Disc::class);
    }
}
