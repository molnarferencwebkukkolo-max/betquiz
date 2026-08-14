<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaVerifier;
use App\Services\EmergencyAdminAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        Request $request,
        RecaptchaVerifier $recaptcha,
        EmergencyAdminAuthenticator $emergencyAdmin,
    ): RedirectResponse
    {
        $recaptcha->validate($request, 'login');

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // A vészhelyzeti admin jelszava nem kerül a users táblába: sikeres ENV-
        // ellenőrzés után közvetlenül jelentkeztetjük be a helyreállított hostadmint.
        if ($user = $emergencyAdmin->attempt($credentials['email'], $credentials['password'])) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Az inaktív fiókot még a jelszó ellenőrzése előtt kizárjuk.
        // Ezzel nem jöhet létre új session, a felhasználó pedig érthető
        // visszajelzést kap a korábbi, látszólagos oldal-újratöltés helyett.
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Ez a felhasználói fiók inaktív.',
            ]);
        }

        if ($user && $user->is_banned) {
            throw ValidationException::withMessages([
                'email' => 'Ez a felhasználói fiók tiltva van.',
            ]);
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true, 'is_banned' => false], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
