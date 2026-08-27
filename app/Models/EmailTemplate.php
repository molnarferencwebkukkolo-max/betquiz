<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class EmailTemplate extends Model
{
    public const VERIFICATION = 'email_verification';
    public const WELCOME = 'welcome';

    protected $fillable = ['key', 'name', 'subject', 'heading', 'body', 'button_text', 'footer', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function content(string $key): self
    {
        return static::query()->where('key', $key)->firstOrFail();
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
