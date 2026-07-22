<nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- Bal oldal: Logo + Menüpontok -->
            <div class="flex items-center gap-8">
                <!-- Logo / Brand -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-extrabold text-2xl text-indigo-600 tracking-wider">
                    🎲 BetQuiz
                </a>

                <!-- Navigációs Menü -->
                <div class="hidden md:flex items-center space-x-1">

                    <!-- 1) JÁTÉK -->
                    <a href="{{ route('dashboard') }}"
                       class="px-4 py-2 rounded-xl text-sm font-bold transition {{ request()->routeIs('dashboard*') || request()->routeIs('quiz*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        🎮 JÁTÉK
                    </a>

                    <!-- 2) Kvízeim / Kvízek -->
                    <a href="{{ route('quizzes.index') }}"
                       class="px-4 py-2 rounded-xl text-sm font-bold transition {{ request()->routeIs('quizzes*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        📋 {{ Auth::user()->isUseradmin() ? 'Kvízek (Admin)' : 'Kvízeim' }}
                    </a>

                    <!-- Kérdésbank (Adminoknak / Haladó usereknek) -->
                    @if(Auth::user()->isUseradmin())
                        <a href="{{ route('questions.index') }}"
                           class="px-4 py-2 rounded-xl text-sm font-bold transition {{ request()->routeIs('questions*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            ❓ Kérdésbank
                        </a>
                    @endif

                    <!-- 3) Felhasználók (Csak Hostadmin) -->
                    @if(Auth::user()->isHostadmin())
                        <a href="{{ route('users.index') }}"
                           class="px-4 py-2 rounded-xl text-sm font-bold transition {{ request()->routeIs('users*') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            👑 Felhasználók
                        </a>
                    @endif

                    <!-- 4) Profilom -->
                    <a href="{{ route('profile.show') }}"
                       class="px-4 py-2 rounded-xl text-sm font-bold transition {{ request()->routeIs('profile*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        👤 Profilom
                    </a>

                    <!-- 5) Szerezz pontot -->
                    <a href="{{ route('pages.points') }}"
                       class="px-4 py-2 rounded-xl text-sm font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 transition border border-amber-200">
                        🎁 Szerezz pontot
                    </a>

                </div>
            </div>

            <!-- Jobb oldal: Egyenleg + Kijelentkezés -->
            <div class="flex items-center gap-4">
                <div class="bg-indigo-100 text-indigo-800 font-extrabold px-3 py-1.5 rounded-full text-xs sm:text-sm flex items-center gap-1">
                    🪙 {{ number_format(Auth::user()->points ?? Auth::user()->balance ?? 0) }} zseton
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-gray-500 hover:text-red-600 transition">
                        Kijelentkezés
                    </button>
                </form>
            </div>

        </div>
    </div>
</nav>
