<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Content;
use App\Models\EmailTemplate;
use App\Models\Quiz;
use App\Notifications\CampaignEmailNotification;
use App\Services\ContentHtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        return view('profile.show', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '🔑 A jelszavad sikeresen megváltozott!');
    }

    /**
     * Admin: Felhasználók listája
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isUseradmin(), 403);

        $search = trim((string) $request->input('search', ''));
        $role = (string) $request->input('role', '');
        $verification = (string) $request->input('verification', '');
        $accountStatus = (string) $request->input('account_status', '');

        $users = User::query()
            ->withCount('createdQuizzes')
            ->when($search !== '', function ($query) use ($search) {
                // A csoportosítás megakadályozza, hogy az OR feltétel felülírja
                // a később alkalmazott szerepkör- vagy hitelesítési szűrést.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, ['user', 'useradmin', 'hostadmin'], true),
                fn ($query) => $query->where('role', $role))
            ->when($verification === 'verified',
                fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($verification === 'unverified',
                fn ($query) => $query->whereNull('email_verified_at'))
            ->when($accountStatus === 'active',
                fn ($query) => $query->where('is_active', true)->where('is_banned', false))
            ->when($accountStatus === 'banned',
                fn ($query) => $query->where('is_banned', true))
            ->when($accountStatus === 'inactive',
                fn ($query) => $query->where('is_active', false))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'hostadmins' => User::where('role', 'hostadmin')->count(),
            'useradmins' => User::where('role', 'useradmin')->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'banned' => User::where('is_banned', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'ad_free' => User::where('is_ad_free', true)->count(),
        ];

        return view('admin.users.index', compact(
            'users',
            'stats',
            'search',
            'role',
            'verification',
            'accountStatus'
        ));
    }

    /**
     * Hostadmin: teljes felhasznaloi adatlap. Hitelesitesi titkokat szandekosan
     * nem adunk at a nezetnek, igy azok veletlenul sem jelenhetnek meg.
     */
    public function show(Request $request, User $user)
    {
        abort_unless($request->user()?->isHostadmin(), 403);

        $user->load([
            'favoriteCategory',
            'legalConsents.content',
            'receivedReferral.inviter:id,name,username',
            'invitedReferrals.invitedUser:id,name,username,created_at',
        ])->loadCount(['createdQuizzes', 'questionReports', 'invitedReferrals']);

        $activity = [
            'answers' => DB::table('user_answers')->where('user_id', $user->id)->count(),
            'correct_answers' => DB::table('user_answers')->where('user_id', $user->id)->where('is_correct', true)->count(),
            'notifications' => DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $user->id)->count(),
            'unread_notifications' => DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $user->id)->whereNull('read_at')->count(),
            'referral_points' => $user->invitedReferrals->sum('reward_points'),
        ];

        $campaigns = EmailTemplate::query()->where('template_type', EmailTemplate::TYPE_CAMPAIGN)
            ->orderBy('name')->get(['id', 'name', 'subject', 'is_active']);
        $recommendedQuizzes = Quiz::query()->where('status', 'approved')->where('is_public', true)
            ->orderBy('title')->get(['id', 'title']);
        $recommendedContents = Content::query()->whereIn('status', ['published', 'scheduled'])
            ->orderBy('title')->get(['id', 'title']);

        return view('admin.users.show', compact('user', 'activity', 'campaigns', 'recommendedQuizzes', 'recommendedContents'));
    }

    /** A felhasználó saját, személyre szabott hitelesítő linkjének admin újraküldése. */
    public function sendVerificationEmail(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            return $this->emailFailure($request, $user, 'verification', $exception);
        }

        return back()->with('success', 'A hitelesítő e-mailt elküldtük ide: '.$user->email);
    }

    /** Egy már megírt automatizált kampánysablon azonnali, egyszeri kiküldése. */
    public function sendCampaignEmail(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        $validated = $request->validate(['email_template_id' => ['required', 'integer', 'exists:email_templates,id']]);
        $template = EmailTemplate::query()->findOrFail($validated['email_template_id']);
        abort_unless($template->isCampaign(), 422);

        try {
            $user->notify(new CampaignEmailNotification($template, true, $user));
        } catch (\Throwable $exception) {
            return $this->emailFailure($request, $user, 'campaign', $exception);
        }

        return back()->with('success', 'A „'.$template->name.'” levelet elküldtük ide: '.$user->email);
    }

    /** Gazdag szövegszerkesztővel összeállított, nem mentett egyedi levél. */
    public function sendCustomEmail(Request $request, User $user, ContentHtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->isHostadmin(), 403);
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'], 'heading' => ['required', 'string', 'max:255'],
            'header_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'content_json' => ['nullable', 'json'], 'content_html' => ['required', 'string', 'max:2000000'],
            'button_text' => ['nullable', 'string', 'max:100'], 'footer' => ['nullable', 'string', 'max:2000'],
            'recommended_quiz_ids' => ['nullable', 'array'], 'recommended_quiz_ids.*' => ['integer', 'exists:quizzes,id'],
            'recommended_content_ids' => ['nullable', 'array'], 'recommended_content_ids.*' => ['integer', 'exists:contents,id'],
            'include_progress' => ['nullable', 'boolean'],
        ]);

        $template = new EmailTemplate([
            'template_type' => EmailTemplate::TYPE_CAMPAIGN,
            'subject' => trim($validated['subject']), 'heading' => trim($validated['heading']),
            'content_json' => filled($validated['content_json'] ?? null) ? json_decode($validated['content_json'], true) : null,
            'content_html' => $sanitizer->sanitize($validated['content_html']),
            'recommended_quiz_ids' => $validated['recommended_quiz_ids'] ?? [],
            'recommended_content_ids' => $validated['recommended_content_ids'] ?? [],
            'include_progress' => $request->boolean('include_progress'),
            'button_text' => $validated['button_text'] ?? null, 'footer' => $validated['footer'] ?? null,
            'is_active' => false,
        ]);
        if ($request->hasFile('header_image')) {
            $template->header_image_path = $request->file('header_image')->store('email/headers', 'public');
        }

        try {
            $user->notify(new CampaignEmailNotification($template, true, $user));
        } catch (\Throwable $exception) {
            return $this->emailFailure($request, $user, 'custom', $exception);
        }

        return back()->with('success', 'Az egyedi e-mailt elküldtük ide: '.$user->email);
    }

    private function emailFailure(Request $request, User $user, string $type, \Throwable $exception): RedirectResponse
    {
        Log::error('A hostadmin felhasználói e-mailje nem volt kézbesíthető.', [
            'sender_id' => $request->user()->id, 'recipient_id' => $user->id,
            'email_type' => $type, 'exception' => $exception,
        ]);

        return back()->withInput()->with('error', 'Az e-mail nem ment ki. Ellenőrizd az SMTP-beállításokat és a Laravel naplót.');
    }

    /**
     * Adminisztrátori fiókállapot- és szerepkör-műveletek.
     */
    public function updateStatus(Request $request, User $user)
    {
        $admin = auth()->user();
        abort_unless($admin?->isUseradmin(), 403);

        $validated = $request->validate([
            'action' => [
                'required',
                Rule::in(['ban', 'unban', 'deactivate', 'activate', 'promote', 'demote', 'enable_ad_free', 'disable_ad_free']),
            ],
        ]);
        $action = $validated['action'];

        // A reklámmentesség kereskedelmi/adminisztratív jogosultság, ezért ezt
        // kizárólag hostadmin kezelheti, a moderációs szerepkörtől függetlenül.
        if (in_array($action, ['enable_ad_free', 'disable_ad_free'], true)) {
            abort_unless($admin->isHostadmin(), 403);
            $user->update(['is_ad_free' => $action === 'enable_ad_free']);

            return back()->with('success', $action === 'enable_ad_free'
                ? "{$user->name} reklámmentességet kapott."
                : "{$user->name} reklámmentessége megszűnt.");
        }

        // Saját fiókot és hostadmint nem engedünk ezen a gyorsfelületen
        // módosítani, mert az adminisztrátori kizáráshoz vezethetne.
        abort_if($admin->is($user) || $user->isHostadmin(), 403);

        if (! $admin->isHostadmin()) {
            // A useradmin kizárólag normál játékosokat moderálhat,
            // más admin jogosultságát vagy állapotát nem módosíthatja.
            abort_unless($user->role === 'user', 403);
            abort_if(in_array($action, ['promote', 'demote'], true), 403);
        }

        if (in_array($action, ['promote', 'demote'], true)) {
            abort_unless($admin->isHostadmin(), 403);
        }

        $message = match ($action) {
            'ban' => $this->setBanned($user, true),
            'unban' => $this->setBanned($user, false),
            'deactivate' => $this->setActive($user, false),
            'activate' => $this->setActive($user, true),
            'promote' => $this->setRole($user, 'useradmin'),
            'demote' => $this->setRole($user, 'user'),
        };

        return back()->with('success', $message);
    }

    private function setBanned(User $user, bool $isBanned): string
    {
        $user->update(['is_banned' => $isBanned]);

        return $isBanned
            ? "{$user->name} felhasználó bannolva lett."
            : "{$user->name} felhasználó banja feloldva.";
    }

    private function setActive(User $user, bool $isActive): string
    {
        $user->update(['is_active' => $isActive]);

        if (! $isActive) {
            // A projekt adatbázis-sessionöket használ; törlésükkel az
            // inaktiválás azonnal érvényesül a már belépett fióknál is.
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return $isActive
            ? "{$user->name} fiókja aktiválva lett."
            : "{$user->name} fiókja inaktiválva lett.";
    }

    private function setRole(User $user, string $role): string
    {
        $user->update(['role' => $role]);

        return $role === 'useradmin'
            ? "{$user->name} useradmin jogosultságot kapott."
            : "{$user->name} useradmin jogosultsága vissza lett vonva.";
    }
}
