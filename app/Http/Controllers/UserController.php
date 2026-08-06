<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
     * Adminisztrátori fiókállapot- és szerepkör-műveletek.
     */
    public function updateStatus(Request $request, User $user)
    {
        $admin = auth()->user();
        abort_unless($admin?->isUseradmin(), 403);

        $validated = $request->validate([
            'action' => [
                'required',
                Rule::in(['ban', 'unban', 'deactivate', 'activate', 'promote', 'demote']),
            ],
        ]);
        $action = $validated['action'];

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
