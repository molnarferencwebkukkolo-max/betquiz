<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    public const EVENTS = [
        'approved' => 'Kvíz jóváhagyása',
        'rejected' => 'Kvíz elutasítása',
        'published' => 'Kvíz publikálása',
        'publication_withdrawn' => 'Jóváhagyás vagy publikálás visszavonása',
        'weekly_report' => 'Heti kvízteljesítmény-jelentés',
        'marketing' => 'Reklám- és marketingüzenetek',
    ];

    public const EMAIL_ONLY_EVENTS = ['marketing'];

    public static function supportsChannel(string $event, string $channel): bool
    {
        return ! ($channel === 'database' && in_array($event, self::EMAIL_ONLY_EVENTS, true));
    }

    protected $fillable = [
        'event',
        'database_enabled',
        'email_enabled',
    ];

    protected function casts(): array
    {
        return [
            'database_enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
