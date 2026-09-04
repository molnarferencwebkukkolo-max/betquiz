<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailCampaignDelivery;
use App\Models\Content;
use App\Models\Quiz;
use App\Notifications\CampaignEmailNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeNotification;
use App\Services\ContentHtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::query()->where('template_type', EmailTemplate::TYPE_SYSTEM)->orderBy('id')->get(),
            'campaigns' => EmailTemplate::query()->where('template_type', EmailTemplate::TYPE_CAMPAIGN)
                ->withCount(['deliveries', 'deliveries as opened_deliveries_count' => fn ($query) => $query->whereNotNull('opened_at')])
                ->orderBy('days_after_registration')->get(),
        ]);
    }

    public function deliveries(Request $request, EmailTemplate $emailTemplate): View
    {
        abort_unless($request->user()?->isHostadmin() && $emailTemplate->isCampaign(), 403);

        $deliveries = EmailCampaignDelivery::query()->with('user:id,username,name,email')
            ->where('email_template_id', $emailTemplate->id)->latest('sent_at')->paginate(50);
        $stats = [
            'sent' => $emailTemplate->deliveries()->count(),
            'opened' => $emailTemplate->deliveries()->whereNotNull('opened_at')->count(),
        ];

        return view('admin.email-templates.deliveries', compact('emailTemplate', 'deliveries', 'stats'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        return $this->editorView(new EmailTemplate(['template_type' => EmailTemplate::TYPE_CAMPAIGN, 'is_active' => false, 'include_progress' => true, 'days_after_registration' => 1]));
    }

    public function edit(Request $request, EmailTemplate $emailTemplate): View
    {
        abort_unless($request->user()?->isHostadmin() && $emailTemplate->isCampaign(), 403);
        return $this->editorView($emailTemplate);
    }

    public function store(Request $request, ContentHtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        $template = EmailTemplate::create($this->campaignData($request, $sanitizer) + [
            'key' => 'campaign_'.Str::lower(Str::random(20)),
            'template_type' => EmailTemplate::TYPE_CAMPAIGN,
        ]);

        return redirect()->route('admin.email-templates.edit', $template)->with('success', 'Az automatizált levél elkészült.');
    }

    public function update(Request $request, EmailTemplate $emailTemplate, ContentHtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        if ($emailTemplate->isCampaign()) {
            $emailTemplate->update($this->campaignData($request, $sanitizer, $emailTemplate));
            return back()->with('success', 'Az automatizált levél elmentve.');
        }
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'], 'heading' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'], 'button_text' => ['nullable', 'string', 'max:100'],
            'footer' => ['nullable', 'string', 'max:2000'], 'is_active' => ['nullable', 'boolean'],
        ]);
        // A dupla opt-in kotelezo, ezert csak a Welcome level kapcsolhato ki.
        $validated['is_active'] = $emailTemplate->key === EmailTemplate::VERIFICATION
            ? true
            : $request->boolean('is_active');
        // A rendszerkulcs nem szerkesztheto, csak a biztonsagos tartalmi mezok.
        $emailTemplate->update($validated);
        return back()->with('success', 'Az e-mail sablon elmentve.');
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        $validated = $request->validate(['test_email' => ['nullable', 'email:rfc', 'max:255']]);
        $recipient = trim((string) ($validated['test_email'] ?? config('services.email_templates.test_recipient') ?? $request->user()->email));

        try {
            $notification = $emailTemplate->isCampaign()
                ? new CampaignEmailNotification($emailTemplate, true, $request->user())
                : ($emailTemplate->key === EmailTemplate::VERIFICATION
                    ? new VerifyEmailNotification($request->user())
                    : new WelcomeNotification(true, $request->user()));
            Notification::route('mail', $recipient)->notify($notification);
        } catch (Throwable $exception) {
            Log::error('Az e-mail sablon tesztkuldese sikertelen.', ['template' => $emailTemplate->key, 'recipient' => $recipient, 'preview_user_id' => $request->user()->id, 'exception' => $exception]);
            return back()->with('error', 'A tesztlevel nem ment ki. Ellenorizd az SMTP-beallitasokat es a Laravel naplot.');
        }
        return back()->with('success', 'A tesztlevelet elküldtük ide: '.$recipient);
    }

    public function uploadImage(Request $request)
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        $validated = $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120']]);
        $path = $validated['image']->store('email/editor', 'public');
        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }

    private function campaignData(Request $request, ContentHtmlSanitizer $sanitizer, ?EmailTemplate $template = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'days_after_registration' => ['required', 'integer', 'min:0', 'max:3650'],
            'subject' => ['required', 'string', 'max:255'], 'heading' => ['required', 'string', 'max:255'],
            'header_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'content_json' => ['nullable', 'json'], 'content_html' => ['required', 'string', 'max:2000000'],
            'button_text' => ['nullable', 'string', 'max:100'], 'footer' => ['nullable', 'string', 'max:2000'],
            'recommended_quiz_ids' => ['nullable', 'array'], 'recommended_quiz_ids.*' => ['integer', 'exists:quizzes,id'],
            'recommended_content_ids' => ['nullable', 'array'], 'recommended_content_ids.*' => ['integer', 'exists:contents,id'],
            'is_active' => ['nullable', 'boolean'], 'include_progress' => ['nullable', 'boolean'],
        ]);
        $validated['content_json'] = filled($validated['content_json'] ?? null) ? json_decode($validated['content_json'], true) : null;
        $validated['content_html'] = $sanitizer->sanitize($validated['content_html']);
        $validated['body'] = Str::limit(trim(strip_tags($validated['content_html'])), 10000, '');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['include_progress'] = $request->boolean('include_progress');
        if ($request->hasFile('header_image')) {
            $validated['header_image_path'] = $request->file('header_image')->store('email/headers', 'public');
            if ($template?->header_image_path) Storage::disk('public')->delete($template->header_image_path);
        }
        unset($validated['header_image']);
        return $validated;
    }

    private function editorView(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.editor', [
            'emailTemplate' => $emailTemplate,
            'quizzes' => Quiz::query()->where('status', 'approved')->where('is_public', true)->orderBy('title')->get(['id', 'title']),
            'contents' => Content::query()->whereIn('status', ['published', 'scheduled'])->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
