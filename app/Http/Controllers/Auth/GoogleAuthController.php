<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LegalConsentService;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google-regisztrációnál még az átirányítás előtt kötelező a két jogi
     * dokumentum elfogadása. A rövid életű session-jelzőt a callback fogyasztja el.
     */
    public function registerRedirect(Request $request): RedirectResponse
    {
        $request->validate([
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
        ]);
        $request->session()->put('google_registration_legal_consent', true);

        return Socialite::driver('google')->redirect();
    }

    public function callback(LegalConsentService $legalConsents, ReferralService $referrals): RedirectResponse
    {
        try {
            /** @var GoogleUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
            $email = strtolower(trim((string) $googleUser->getEmail()));
            $googleId = trim((string) $googleUser->getId());
            $emailVerified = filter_var($googleUser->user['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

            if ($email === '' || $googleId === '' || ! $emailVerified) {
                throw ValidationException::withMessages([
                    'email' => 'A Google-fiókhoz igazolt e-mail-cím szükséges.',
                ]);
            }

            [$user, $created] = DB::transaction(function () use ($googleUser, $email, $googleId): array {
                $user = User::query()
                    ->where('google_id', $googleId)
                    ->orWhere('email', $email)
                    ->lockForUpdate()
                    ->first();

                if ($user && $user->google_id && $user->google_id !== $googleId) {
                    throw ValidationException::withMessages([
                        'email' => 'Ez az e-mail-cím már egy másik Google-fiókhoz kapcsolódik.',
                    ]);
                }

                if ($user) {
                    $this->ensureAccountCanLogin($user);

                    $user->forceFill([
                        'google_id' => $googleId,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();

                    return [$user, false];
                }

                if (! request()->session()->pull('google_registration_legal_consent', false)) {
                    throw ValidationException::withMessages([
                        'accept_terms' => 'Új Google-fiók létrehozásához fogadd el az ÁSZF-et és az adatkezelési szabályzatot.',
                    ]);
                }

                $user = User::create([
                    'name' => trim((string) $googleUser->getName()) ?: strstr($email, '@', true),
                    'email' => $email,
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                    'password' => null,
                    'points' => 1000,
                ]);

                return [$user, true];
            });

            if ($created) {
                $legalConsents->recordRegistrationConsents($user, request());
                $referrals->attachFromSession($user, request());
                event(new Registered($user));
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            if ($created || ! $user->username) {
                return redirect()->route('profile.edit')->with(
                    'status',
                    'google-onboarding'
                );
            }

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'email' => 'A Google-bejelentkezés nem sikerült vagy megszakadt. Kérjük, próbáld újra.',
            ]);
        }
    }

    private function ensureAccountCanLogin(User $user): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Ez a felhasználói fiók inaktív.']);
        }

        if ($user->is_banned) {
            throw ValidationException::withMessages(['email' => 'Ez a felhasználói fiók tiltva van.']);
        }
    }
}
