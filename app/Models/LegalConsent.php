<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalConsent extends Model
{
    protected $fillable = [
        'user_id', 'content_id', 'consent_type', 'content_version',
        'document_url', 'accepted_at', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'content_version' => 'integer',
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
