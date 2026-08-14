<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Felhasználók - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 pb-12">

@include('layouts.navigation')

@php
    $roleLabels = [
        'hostadmin' => ['Host admin', 'bg-purple-100 text-purple-700'],
        'useradmin' => ['User admin', 'bg-indigo-100 text-indigo-700'],
        'user' => ['Játékos', 'bg-gray-100 text-gray-700'],
    ];
@endphp

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-800">Felhasználók</h1>
        <p class="mt-2 text-sm text-gray-500">Regisztrált fiókok, jogosultságok és alapvető aktivitási adatok.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-green-300 bg-green-100 p-4 font-bold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-red-300 bg-red-100 p-4 text-red-800">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-7">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">Összes fiók</div>
            <div class="mt-1 text-3xl font-black text-gray-800">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">Host admin</div>
            <div class="mt-1 text-3xl font-black text-purple-700">{{ number_format($stats['hostadmins']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">User admin</div>
            <div class="mt-1 text-3xl font-black text-indigo-700">{{ number_format($stats['useradmins']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">Hitelesített e-mail</div>
            <div class="mt-1 text-3xl font-black text-green-700">{{ number_format($stats['verified']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">Bannolt</div>
            <div class="mt-1 text-3xl font-black text-red-700">{{ number_format($stats['banned']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">Inaktív</div>
            <div class="mt-1 text-3xl font-black text-gray-500">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="text-sm font-bold text-gray-500">Reklámmentes</div>
            <div class="mt-1 text-3xl font-black text-purple-700">{{ number_format($stats['ad_free']) }}</div>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.users.index') }}"
          class="mb-6 grid grid-cols-1 items-end gap-4 rounded-3xl bg-white p-5 shadow-sm md:grid-cols-5">
        <label class="block md:col-span-2">
            <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Keresés</span>
            <input type="search" name="search" value="{{ $search }}"
                   class="w-full rounded-xl border border-gray-300 px-4 py-3"
                   placeholder="Név vagy e-mail-cím">
        </label>
        <label class="block">
            <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Szerepkör</span>
            <select name="role" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3">
                <option value="">Minden szerepkör</option>
                <option value="user" @selected($role === 'user')>Játékos</option>
                <option value="useradmin" @selected($role === 'useradmin')>User admin</option>
                <option value="hostadmin" @selected($role === 'hostadmin')>Host admin</option>
            </select>
        </label>
        <label class="block">
            <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">E-mail-státusz</span>
            <select name="verification" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3">
                <option value="">Minden státusz</option>
                <option value="verified" @selected($verification === 'verified')>Hitelesített</option>
                <option value="unverified" @selected($verification === 'unverified')>Nincs hitelesítve</option>
            </select>
        </label>
        <label class="block">
            <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Fiókállapot</span>
            <select name="account_status" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3">
                <option value="">Minden állapot</option>
                <option value="active" @selected($accountStatus === 'active')>Aktív</option>
                <option value="banned" @selected($accountStatus === 'banned')>Bannolt</option>
                <option value="inactive" @selected($accountStatus === 'inactive')>Inaktív</option>
            </select>
        </label>
        <div class="flex gap-3 md:col-span-5 md:justify-end">
            @if($search !== '' || $role !== '' || $verification !== '' || $accountStatus !== '')
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-xl border border-gray-300 px-5 py-3 font-bold text-gray-600 hover:bg-gray-50">
                    Szűrők törlése
                </a>
            @endif
            <button class="rounded-xl bg-indigo-600 px-6 py-3 font-extrabold text-white hover:bg-indigo-700">
                Szűrés
            </button>
        </div>
    </form>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr class="text-left text-xs font-extrabold uppercase tracking-wide text-gray-500">
                    <th class="px-6 py-4">Felhasználó</th>
                    <th class="px-6 py-4">Állapot</th>
                    <th class="px-6 py-4">Szerepkör</th>
                    <th class="px-6 py-4">Pont</th>
                    <th class="px-6 py-4">Kvízek</th>
                    <th class="px-6 py-4">E-mail</th>
                    <th class="px-6 py-4">Regisztráció</th>
                    <th class="px-6 py-4 text-right">Műveletek</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($users as $listedUser)
                    @php($roleMeta = $roleLabels[$listedUser->role] ?? [$listedUser->role, 'bg-gray-100 text-gray-700'])
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-extrabold text-gray-800">{{ $listedUser->name }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ $listedUser->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col items-start gap-1">
                                @if(!$listedUser->is_active)
                                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-extrabold text-gray-700">Inaktív</span>
                                @else
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-extrabold text-green-700">Aktív</span>
                                @endif
                                @if($listedUser->is_banned)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-extrabold text-red-700">Bannolt</span>
                                @endif
                                @if($listedUser->is_ad_free)
                                    <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-extrabold text-purple-700">Reklámmentes</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $roleMeta[1] }}">
                                {{ $roleMeta[0] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-bold text-amber-600">
                            {{ number_format($listedUser->points ?? 0, 0, ',', ' ') }} PT
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-bold text-gray-700">
                            {{ $listedUser->created_quizzes_count }}
                        </td>
                        <td class="px-6 py-4">
                            @if($listedUser->email_verified_at)
                                <span class="font-bold text-green-700">Hitelesített</span>
                                <div class="mt-1 text-xs text-gray-400">{{ $listedUser->email_verified_at->format('Y. m. d.') }}</div>
                            @else
                                <span class="font-bold text-amber-700">Nincs hitelesítve</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $listedUser->created_at?->format('Y. m. d. H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            @php($currentAdmin = auth()->user())
                            @php($canModerate = !$currentAdmin->is($listedUser) && !$listedUser->isHostadmin() && ($currentAdmin->isHostadmin() || $listedUser->role === 'user'))
                            @php($canChangeRole = $currentAdmin->isHostadmin() && !$currentAdmin->is($listedUser) && !$listedUser->isHostadmin())

                            @if($canModerate || $canChangeRole)
                                <div class="flex min-w-48 flex-wrap justify-end gap-2">
                                    @if($canModerate)
                                        <form method="POST" action="{{ route('admin.users.status', $listedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="{{ $listedUser->is_banned ? 'unban' : 'ban' }}">
                                            <button class="rounded-lg border px-3 py-2 text-xs font-extrabold {{ $listedUser->is_banned ? 'border-green-300 text-green-700' : 'border-red-300 text-red-700' }}"
                                                    onclick="return confirm('Biztosan módosítod a felhasználó ban állapotát?');">
                                                {{ $listedUser->is_banned ? 'Ban feloldása' : 'Bannolás' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.status', $listedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="{{ $listedUser->is_active ? 'deactivate' : 'activate' }}">
                                            <button class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-extrabold text-gray-700"
                                                    onclick="return confirm('Biztosan módosítod a fiók aktív állapotát?');">
                                                {{ $listedUser->is_active ? 'Inaktiválás' : 'Aktiválás' }}
                                            </button>
                                        </form>
                                    @endif

                                    @if($canChangeRole && in_array($listedUser->role, ['user', 'useradmin'], true))
                                        <form method="POST" action="{{ route('admin.users.status', $listedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="{{ $listedUser->role === 'useradmin' ? 'demote' : 'promote' }}">
                                            <button class="rounded-lg border border-indigo-300 px-3 py-2 text-xs font-extrabold text-indigo-700"
                                                    onclick="return confirm('Biztosan módosítod a felhasználó jogosultságát?');">
                                                {{ $listedUser->role === 'useradmin' ? 'Adminjog elvétele' : 'Useradminná tétel' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <div class="text-right text-xs font-bold text-gray-400">Nem módosítható</div>
                            @endif
                            @if($currentAdmin->isHostadmin())
                                <form method="POST" action="{{ route('admin.users.status', $listedUser) }}" class="mt-2 flex justify-end">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="{{ $listedUser->is_ad_free ? 'disable_ad_free' : 'enable_ad_free' }}">
                                    <button class="rounded-lg border border-purple-300 px-3 py-2 text-xs font-extrabold text-purple-700">
                                        {{ $listedUser->is_ad_free ? 'Reklámok visszakapcsolása' : 'Reklámmentesség adása' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            A megadott feltételekkel nem található felhasználó.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</main>
</body>
</html>
