<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Értesítések - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
@include('layouts.navigation')

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Értesítések</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">
                {{ auth()->user()->unreadNotifications()->count() }} olvasatlan értesítés
            </p>
        </div>

        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-extrabold transition">
                    Összes megjelölése olvasottként
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 font-bold">
            {{ session('success') }}
        </div>
    @endif

    <section class="space-y-4">
        @forelse($notifications as $notification)
            @php($data = $notification->data)
            <article class="rounded-3xl border p-5 sm:p-6 shadow-sm transition {{ $notification->read_at ? 'bg-white border-slate-200' : 'bg-indigo-50 border-indigo-200' }}">
                <div class="flex items-start gap-4">
                    <div class="notification-event-icon {{ $notification->read_at ? 'is-read' : '' }}" aria-hidden="true">
                        🔔
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-extrabold text-slate-900">{{ $data['title'] ?? 'Értesítés' }}</h2>
                                    @unless($notification->read_at)
                                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-[0.65rem] font-extrabold uppercase text-white">Új</span>
                                    @endunless
                                </div>
                                <p class="mt-1 text-sm font-bold text-indigo-700">{{ $data['quiz_title'] ?? '' }}</p>
                            </div>
                            <time class="text-xs font-semibold text-slate-400" datetime="{{ $notification->created_at->toIso8601String() }}">
                                {{ $notification->created_at->diffForHumans() }}
                            </time>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</p>

                        @if(!empty($data['reason']))
                            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <p class="text-xs font-extrabold uppercase tracking-wide text-amber-700 mb-1">Adminisztrátori indok</p>
                                <p class="text-sm font-semibold text-amber-950 whitespace-pre-line">{{ $data['reason'] }}</p>
                            </div>
                        @endif

                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            @if(!empty($data['url']))
                                <a href="{{ $data['url'] }}" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-700 text-white text-sm font-extrabold transition">
                                    Kvíz megnyitása
                                </a>
                            @endif
                            @unless($notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-4 py-2 rounded-xl border border-indigo-200 bg-white hover:bg-indigo-100 text-indigo-700 text-sm font-extrabold transition">
                                        Megjelölés olvasottként
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                <div class="text-4xl mb-3">🔕</div>
                <h2 class="text-xl font-extrabold text-slate-800">Még nincs értesítésed</h2>
                <p class="mt-2 text-sm text-slate-500">A kívízeid moderációs változásai itt jelennek majd meg.</p>
            </div>
        @endforelse
    </section>

    <div class="mt-8">{{ $notifications->links() }}</div>
</main>
</body>
</html>
