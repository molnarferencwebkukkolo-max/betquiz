<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdPlacement extends Model
{
    protected $fillable = ['key', 'name', 'format', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function advertisements(): BelongsToMany
    {
        return $this->belongsToMany(Advertisement::class, 'advertisement_placement');
    }
}
