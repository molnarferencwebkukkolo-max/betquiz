<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaVerifier;
use App\Services\LegalConsentService;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, RecaptchaVerifier $recaptcha, LegalConsentService $legalConsents, ReferralService $referrals): RedirectResponse
    {
        $recaptcha->validate($request, 'register');

        $request->merge([
            'username' => mb_strtolower(trim((string) $request->input('username'))),
        ]);

        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[\pL\pN_-]+$/u', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'points' => 1000, // Kezdőtőke 1000 PT!
        ]);

        $legalConsents->recordRegistrationConsents($user, $request);
        $referrals->attachFromSession($user, $request);

        try {
            // A regisztrációs esemény több, külső kézbesítést is végző listenert
            // indít (például a hitelesítő e-mailt). Egy átmeneti SMTP-hiba miatt
            // a már létrehozott fiók nem maradhat félrevezető HTTP 500 oldalon.
            event(new Registered($user));
        } catch (\Throwable $exception) {
            Log::error('A regisztráció utáni értesítések kézbesítése sikertelen volt.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);
        }

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}
