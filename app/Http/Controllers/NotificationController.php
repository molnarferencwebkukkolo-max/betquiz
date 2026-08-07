<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        // A kapcsolat felőli lekérdezés megakadályozza, hogy egy felhasználó
        // más értesítésének UUID-ját olvasottra állíthassa.
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return back()->with('success', 'Az értesítést olvasottnak jelöltük.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Minden értesítést olvasottnak jelöltünk.');
    }
}
