<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilom - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-4xl mx-auto px-4 py-10">

    <h1 class="text-3xl font-extrabold text-gray-800 mb-6">👤 Profilom</h1>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-2xl font-bold">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <!-- Bal oldal: Felhasználói kártya -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100 text-center h-fit">
            <div class="w-20 h-20 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-extrabold text-3xl mx-auto mb-4">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $user->email }}</p>

            <div class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                    {{ $user->isHostadmin() ? 'bg-purple-100 text-purple-700' : ($user->isUseradmin() ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                {{ $user->role ?? 'user' }}
            </div>
        </div>

        <!-- Jobb oldal: Műveletek -->
        <div class="md:col-span-2 space-y-6">

            <!-- 🛠️ DEVTOOL: Szerepkör Váltó (Teszteléshez) -->
            <div class="bg-amber-50 rounded-3xl p-6 border-2 border-dashed border-amber-300">
                <div class="flex items-center gap-2 mb-2 text-amber-900 font-extrabold text-lg">
                    <span>⚡ Fejlesztői Eszköz: Szerepkör Váltás</span>
                </div>
                <p class="text-xs text-amber-700 mb-4">
                    Itt menet közben váltogathatsz a szerepkörök között, hogy leteszteld, mit lát a sima User, a Moderátor (Useradmin) és a Superadmin (Hostadmin).
                </p>

                <form action="{{ route('profile.switch-role') }}" method="POST" class="flex flex-wrap gap-2">
                    @csrf

                    <button type="submit" name="role" value="user"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $user->role === 'user' ? 'bg-gray-800 text-white shadow-md' : 'bg-white text-gray-700 border hover:bg-gray-100' }}">
                        👤 User (Sima Játékos)
                    </button>

                    <button type="submit" name="role" value="useradmin"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $user->role === 'useradmin' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-blue-700 border hover:bg-blue-50' }}">
                        🛡️ Useradmin (Moderátor)
                    </button>

                    <button type="submit" name="role" value="hostadmin"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $user->role === 'hostadmin' ? 'bg-purple-600 text-white shadow-md' : 'bg-white text-purple-700 border hover:bg-purple-50' }}">
                        👑 Hostadmin (Superadmin)
                    </button>
                </form>
            </div>

            <!-- Jelszó módosítása -->
            <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">🔑 Jelszó Módosítása</h3>

                <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jelenlegi jelszó:</label>
                        <input type="password" name="current_password" required class="w-full p-3 border rounded-xl">
                        @error('current_password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Új jelszó:</label>
                        <input type="password" name="password" required class="w-full p-3 border rounded-xl">
                        @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Új jelszó megerősítése:</label>
                        <input type="password" name="password_confirmation" required class="w-full p-3 border rounded-xl">
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow transition">
                        Mentés
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

</body>
</html>
