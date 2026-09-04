<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignDelivery extends Model
{
    protected $fillable = ['email_template_id', 'user_id', 'tracking_token', 'sent_at', 'opened_at'];
    protected function casts(): array { return ['sent_at' => 'datetime', 'opened_at' => 'datetime']; }
    public function template(): BelongsTo { return $this->belongsTo(EmailTemplate::class, 'email_template_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
