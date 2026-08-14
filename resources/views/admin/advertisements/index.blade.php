<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hirdetések - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="ad-admin-page min-h-screen pb-12">
@include('layouts.navigation')

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <header class="mb-8">
        <span class="text-xs font-black uppercase tracking-widest text-purple-400">KwizzGo monetizáció</span>
        <h1 class="mt-2 text-3xl font-black">Hirdetések kezelése</h1>
        <p class="mt-2 text-sm text-slate-400">Képes partnerbannerek és ellenőrzött Google AdSense-egységek pozícióalapú kezelése.</p>
    </header>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 font-bold text-emerald-300">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-rose-200">
            <strong>A hirdetést nem sikerült elmenteni:</strong>
            <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="ad-admin-card mb-8 p-6">
        <h2 class="text-xl font-black">Új hirdetés</h2>
        <p class="mt-1 text-sm text-slate-400">A rotáció oldalbetöltésenként választ egy aktív kreatívot, a megadott súlyok alapján.</p>
        <form method="POST" action="{{ route('admin.advertisements.store') }}" enctype="multipart/form-data"
              class="ad-editor mt-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
            @csrf
            @include('admin.advertisements.partials.fields', ['advertisement' => null])
            <div class="lg:col-span-2 flex justify-end">
                <button class="rounded-xl bg-purple-600 px-6 py-3 font-black text-white hover:bg-purple-500">Hirdetés létrehozása</button>
            </div>
        </form>
    </section>

    <section>
        <div class="mb-4 flex items-end justify-between gap-4">
            <div><h2 class="text-xl font-black">Rögzített hirdetések</h2><p class="mt-1 text-sm text-slate-400">{{ $advertisements->count() }} kreatív</p></div>
        </div>

        <div class="space-y-5">
            @forelse($advertisements as $advertisement)
                <article class="ad-admin-card overflow-hidden">
                    <details>
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-4 p-5">
                            <div class="flex items-center gap-4">
                                @if($advertisement->type === 'image' && $advertisement->image_path)
                                    <img src="{{ asset('storage/'.$advertisement->image_path) }}" alt="" class="h-16 w-28 rounded-lg object-cover">
                                @else
                                    <div class="grid h-16 w-28 place-items-center rounded-lg bg-purple-500/10 text-xs font-black text-purple-300">AdSense</div>
                                @endif
                                <div>
                                    <h3 class="font-black text-white">{{ $advertisement->name }}</h3>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full px-2 py-1 {{ $advertisement->is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ $advertisement->is_active ? 'Aktív' : 'Inaktív' }}</span>
                                        <span class="rounded-full bg-purple-500/15 px-2 py-1 text-purple-300">Súly: {{ $advertisement->weight }}</span>
                                        @foreach($advertisement->placements as $placement)<span class="rounded-full bg-slate-500/15 px-2 py-1 text-slate-300">{{ $placement->name }}</span>@endforeach
                                    </div>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-purple-300">Szerkesztés ▾</span>
                        </summary>
                        <div class="border-t border-slate-700/70 p-5">
                            <form method="POST" action="{{ route('admin.advertisements.update', $advertisement) }}" enctype="multipart/form-data"
                                  class="ad-editor grid grid-cols-1 gap-5 lg:grid-cols-2">
                                @csrf @method('PUT')
                                @include('admin.advertisements.partials.fields', ['advertisement' => $advertisement])
                                <div class="lg:col-span-2 flex flex-wrap justify-end gap-3">
                                    <button class="rounded-xl bg-purple-600 px-6 py-3 font-black text-white hover:bg-purple-500">Módosítások mentése</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('admin.advertisements.destroy', $advertisement) }}" class="mt-3 flex justify-end">
                                @csrf @method('DELETE')
                                <button class="rounded-xl border border-rose-500/40 px-5 py-2 text-sm font-black text-rose-300"
                                        onclick="return confirm('Biztosan végleg törlöd ezt a hirdetést?');">Hirdetés törlése</button>
                            </form>
                        </div>
                    </details>
                </article>
            @empty
                <div class="ad-admin-card p-10 text-center text-slate-400">Még nincs feltöltött hirdetés.</div>
            @endforelse
        </div>
    </section>
</main>

<script>
    document.querySelectorAll('.ad-editor').forEach((form) => {
        const refresh = () => {
            const type = form.querySelector('[name="type"]')?.value;
            form.querySelectorAll('[data-for-ad-type]').forEach((field) => {
                field.hidden = field.dataset.forAdType !== type;
            });
        };
        form.querySelector('[name="type"]')?.addEventListener('change', refresh);
        refresh();
    });
</script>
</body>
</html>
