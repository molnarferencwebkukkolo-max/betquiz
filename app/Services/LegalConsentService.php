<?php

namespace App\Services;

use App\Models\Content;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LegalConsentService
{
    /**
     * A regisztráció pillanatában hatályos dokumentumok pontos verzióját
     * naplózzuk. Így egy későbbi szövegmódosítás nem írja felül a bizonyítékot.
     */
    public function recordRegistrationConsents(User $user, Request $request): void
    {
        $documents = Content::query()
            ->where('type', 'page')
            ->whereIn('slug', ['aszf', 'adatkezeles'])
            ->get()
            ->keyBy('slug');

        DB::transaction(function () use ($documents, $request, $user) {
            foreach (['terms' => 'aszf', 'privacy' => 'adatkezeles'] as $type => $slug) {
                $content = $documents->get($slug);
                $user->legalConsents()->create([
                    'content_id' => $content?->id,
                    'consent_type' => $type,
                    'content_version' => (int) ($content?->version ?? 0),
                    'document_url' => url('/'.$slug),
                    'accepted_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                ]);
            }
        });
    }
}
