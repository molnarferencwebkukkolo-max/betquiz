<?php

namespace App\Notifications;

use App\Models\Content;
use App\Models\EmailTemplate;
use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\EmailCampaignDelivery;

class CampaignEmailNotification extends Notification
{
    use Queueable;

    public function __construct(public EmailTemplate $template, public bool $force = false, public ?\App\Models\User $previewUser = null, public ?EmailCampaignDelivery $delivery = null) {}

    public function via(object $notifiable): array
    {
        return ($this->force || ($this->template->is_active && $notifiable->wantsNotification('recommendations', 'mail'))) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $notifiable = $this->previewUser ?? $notifiable;
        $quizResults = collect();
        if ($this->template->include_progress) {
            // A tartós első válaszokból ugyanazt a találati arányt képezzük,
            // amelyet a profil és a hostadmin adatlap is használ.
            $quizResults = DB::table('user_answers')->join('quizzes', 'quizzes.id', '=', 'user_answers.quiz_id')
                ->where('user_answers.user_id', $notifiable->id)
                ->selectRaw('quizzes.id, quizzes.slug, quizzes.title, COUNT(*) as answers, SUM(CASE WHEN user_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers, MAX(user_answers.created_at) as last_played_at')
                ->groupBy('quizzes.id', 'quizzes.slug', 'quizzes.title')->orderByDesc('last_played_at')->limit(5)->get();
        }

        $quizzes = Quiz::query()->whereIn('id', $this->template->recommended_quiz_ids ?? [])->get();
        $contents = Content::query()->whereIn('id', $this->template->recommended_content_ids ?? [])->get();
        $html = $this->template->render($this->template->content_html ?? '', $notifiable);
        // A böngészőben relatív feltöltési URL az e-mail kliensben csak abszolút
        // címmel működik, ezért kiküldéskor a nyilvános alkalmazáscímet elé tesszük.
        $html = str_replace(['src="/storage/', "src='/storage/"], ['src="'.url('/storage/'), "src='".url('/storage/')], $html);

        return (new MailMessage)
            ->subject($this->template->render($this->template->subject, $notifiable))
            ->view('emails.campaign', [
                'template' => $this->template, 'user' => $notifiable, 'html' => $html,
                'quizResults' => $quizResults, 'quizzes' => $quizzes, 'contents' => $contents,
                'headerImageUrl' => $this->template->header_image_path ? url(Storage::disk('public')->url($this->template->header_image_path)) : null,
                'trackingUrl' => $this->delivery?->tracking_token ? route('email-tracking.open', $this->delivery->tracking_token) : null,
            ]);
    }
}
