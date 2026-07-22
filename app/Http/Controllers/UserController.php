<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
     * 🛠️ DEVTOOL: Szerepkör gyorsváltó (Teszteléshez)
     */
    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:user,useradmin,hostadmin',
        ]);

        $user = Auth::user();
        $user->role = $request->role;
        $user->save();

        return back()->with('success', "🎭 Szerepkör sikeresen átállítva: {$request->role}!");
    }
}
