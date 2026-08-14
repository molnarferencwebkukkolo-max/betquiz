<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRevision extends Model
{
    protected $fillable = ['content_id', 'published_by', 'version', 'snapshot', 'published_at'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'published_at' => 'datetime', 'version' => 'integer'];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
