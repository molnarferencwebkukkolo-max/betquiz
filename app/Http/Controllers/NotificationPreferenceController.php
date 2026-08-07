<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['array'],
            'preferences.*.event' => ['required', Rule::in(array_keys(NotificationPreference::EVENTS))],
            'preferences.*.database' => ['nullable', 'boolean'],
            'preferences.*.email' => ['nullable', 'boolean'],
        ]);

        $submittedPreferences = collect($validated['preferences'] ?? [])->keyBy('event');

        DB::transaction(function () use ($request, $submittedPreferences): void {
            foreach (NotificationPreference::EVENTS as $event => $label) {
                $submitted = $submittedPreferences->get($event, []);

                $request->user()->notificationPreferences()->updateOrCreate(
                    ['event' => $event],
                    [
                        'database_enabled' => (bool) ($submitted['database'] ?? false),
                        'email_enabled' => (bool) ($submitted['email'] ?? false),
                    ]
                );
            }
        });

        return back()->with('success', 'Értesítési beállítások mentve.');
    }
}
