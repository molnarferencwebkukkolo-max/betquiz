<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RecaptchaVerifier
{
    /**
     * A böngészőben kapott reCAPTCHA tokent mindig a Google szerverével
     * ellenőrizzük; a kliensoldali jelölőnégyzet önmagában nem tekinthető bizonyítéknak.
     */
    public function validate(Request $request, string $expectedAction): void
    {
        if (! config('recaptcha.enabled')) {
            return;
        }

        $siteKey = config('recaptcha.site_key');
        $secretKey = config('recaptcha.secret_key');

        if (! is_string($siteKey) || $siteKey === '' || ! is_string($secretKey) || $secretKey === '') {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'A robotellenőrzés jelenleg nincs megfelelően konfigurálva.',
            ]);
        }

        $token = $request->string('g-recaptcha-response')->trim()->toString();
        if ($token === '') {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Kérjük, igazold, hogy nem vagy robot.',
            ]);
        }

        try {
            $result = Http::asForm()
                ->timeout(8)
                ->post(config('recaptcha.verify_url'), [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (ConnectionException) {
            $this->failed();
        }

        $score = $result->json('score');

        // A sikeres v3 válasz mellett az action egyezését és a kockázati
        // pontszámot is ellenőrizzük, így másik űrlap tokenje nem játszható újra.
        if (! $result->successful()
            || $result->json('success') !== true
            || $result->json('action') !== $expectedAction
            || ! is_numeric($score)
            || (float) $score < (float) config('recaptcha.minimum_score', 0.5)) {
            $this->failed();
        }
    }

    private function failed(): never
    {
        throw ValidationException::withMessages([
            'g-recaptcha-response' => 'A robotellenőrzés nem sikerült. Kérjük, próbáld újra.',
        ]);
    }
}
