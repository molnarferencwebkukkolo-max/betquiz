<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        return view('admin.email-templates.index', ['templates' => EmailTemplate::query()->orderBy('id')->get()]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);
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
        try {
            $request->user()->notify($emailTemplate->key === EmailTemplate::VERIFICATION ? new VerifyEmailNotification() : new WelcomeNotification(true));
        } catch (Throwable $exception) {
            Log::error('Az e-mail sablon tesztkuldese sikertelen.', ['template' => $emailTemplate->key, 'recipient_id' => $request->user()->id, 'exception' => $exception]);
            return back()->with('error', 'A tesztlevel nem ment ki. Ellenorizd az SMTP-beallitasokat es a Laravel naplot.');
        }
        return back()->with('success', 'A tesztlevelet elkuldtuk ide: '.$request->user()->email);
    }
}
