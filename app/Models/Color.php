<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Color extends Model
{
    protected $fillable = ['name'];

    /**
     * @return BelongsToMany<Disc>
     */
    public function discs(): BelongsToMany
    {
        return $this->belongsToMany(Disc::class, 'disc_colors');
    }
}
