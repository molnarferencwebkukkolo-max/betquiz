<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            // Az SMTP szolgáltatás átmeneti hibája nem válhat HTTP 500 válasszá.
            // A részletes kivétel a naplóban marad, a felhasználó pedig világos
            // visszajelzést kap, és később újra megpróbálhatja a küldést.
            Log::error('A hitelesítő e-mail újraküldése sikertelen volt.', [
                'user_id' => $request->user()->id,
                'exception' => $exception,
            ]);

            return back()->with('status', 'verification-link-failed');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
