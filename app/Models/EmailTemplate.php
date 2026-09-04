<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    public const VERIFICATION = 'email_verification';
    public const WELCOME = 'welcome';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_CAMPAIGN = 'campaign';

    protected $fillable = ['key', 'template_type', 'name', 'days_after_registration', 'subject', 'heading', 'header_image_path', 'body', 'content_json', 'content_html', 'recommended_quiz_ids', 'recommended_content_ids', 'include_progress', 'button_text', 'footer', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'include_progress' => 'boolean', 'days_after_registration' => 'integer', 'content_json' => 'array', 'recommended_quiz_ids' => 'array', 'recommended_content_ids' => 'array'];
    }

    public static function content(string $key): self
    {
        return static::query()->where('key', $key)->firstOrFail();
    }

    public function isCampaign(): bool
    {
        return $this->template_type === self::TYPE_CAMPAIGN;
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(EmailCampaignDelivery::class);
    }

    /** A szerkesztheto mezokben engedelyezett, biztonsagos szoveges valtozok. */
    public function render(string $value, User $user): string
    {
        return strtr($value, [
            '{{username}}' => $user->username ?: $user->name,
            '{{email}}' => $user->email,
        ]);
    }

    /**
     * A sorokat egyenkent adjuk a Laravel levelhez. Az ures sor nem veszhet el:
     * ket Enter kozott egy nem torheto szokoz tartja meg a lathato tavolsagot.
     */
    public function appendLines(MailMessage $mail, ?string $value, User $user): MailMessage
    {
        foreach (preg_split('/\R/', $this->render($value ?? '', $user)) as $line) {
            $mail->line($line === '' ? new HtmlString('&nbsp;') : $line);
        }

        return $mail;
    }
}
