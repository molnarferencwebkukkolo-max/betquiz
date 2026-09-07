<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPointAward extends Model
{
    protected $fillable = ['user_id', 'awarded_by', 'amount', 'reason'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
