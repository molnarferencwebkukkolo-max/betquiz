<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategóriák kezelése - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 pb-12">

@include('layouts.navigation')

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-800">Kategóriák kezelése</h1>
        <p class="mt-2 text-sm text-gray-500">
            Itt hozhatsz létre új kategóriákat, illetve módosíthatod vagy inaktiválhatod a meglévőket.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-green-300 bg-green-100 p-4 font-bold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-red-300 bg-red-100 p-4 text-red-800">
            <p class="font-bold">A mentés nem sikerült:</p>
            <ul class="mt-2 list-disc pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-8 rounded-3xl bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-xl font-extrabold text-gray-800">Új kategória</h2>
        <form method="POST" action="{{ route('admin.categories.store') }}"
              class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
            @csrf
            <label class="block">
                <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Magyar név *</span>
                <input name="name[hu]" value="{{ old('name.hu') }}" required maxlength="255"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3" placeholder="Például: Történelem">
            </label>
            <label class="block">
                <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Angol név</span>
                <input name="name[en]" value="{{ old('name.en') }}" maxlength="255"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3" placeholder="Például: History">
            </label>
            <label class="block">
                <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Font Awesome ikon</span>
                <input name="icon" value="{{ old('icon', 'fa-folder') }}" maxlength="50"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3" placeholder="fa-folder">
            </label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded" @checked(old('is_active', true))>
                    Aktív
                </label>
                <button class="ml-auto rounded-xl bg-indigo-600 px-5 py-3 font-extrabold text-white hover:bg-indigo-700">
                    Létrehozás
                </button>
            </div>
        </form>
    </section>

    <div class="space-y-4">
        @forelse($categories as $category)
            <section class="rounded-3xl bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.categories.update', $category) }}"
                      class="grid grid-cols-1 items-end gap-4 lg:grid-cols-6">
                    @csrf
                    @method('PUT')
                    <label class="block lg:col-span-2">
                        <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Magyar név *</span>
                        <input name="name[hu]" value="{{ $category->name['hu'] ?? $category->translated_name }}" required maxlength="255"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3">
                    </label>
                    <label class="block lg:col-span-2">
                        <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Angol név</span>
                        <input name="name[en]" value="{{ $category->name['en'] ?? '' }}" maxlength="255"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-extrabold uppercase text-gray-500">Ikon</span>
                        <input name="icon" value="{{ $category->icon }}" maxlength="50"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3">
                    </label>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700">
                            <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded" @checked($category->is_active)>
                            Aktív
                        </label>
                        <button class="ml-auto rounded-xl bg-indigo-600 px-4 py-3 font-extrabold text-white hover:bg-indigo-700">
                            Mentés
                        </button>
                    </div>
                </form>

                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4 text-sm text-gray-500">
                    <span>Slug: <strong>{{ $category->slug }}</strong></span>
                    <span>{{ $category->quizzes_count }} kvíz</span>
                    <span>{{ $category->questions_count }} kérdés</span>

                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="ml-auto"
                          onsubmit="return confirm('Biztosan törlöd ezt a kategóriát?');">
                        @csrf
                        @method('DELETE')
                        <button class="font-bold text-red-600 hover:text-red-800"
                                @disabled($category->quizzes_count > 0 || $category->questions_count > 0)
                                title="{{ $category->quizzes_count > 0 || $category->questions_count > 0 ? 'Használatban lévő kategória nem törölhető.' : '' }}">
                            Törlés
                        </button>
                    </form>
                </div>
            </section>
        @empty
            <div class="rounded-3xl bg-white p-8 text-center text-gray-500 shadow-sm">
                Még nincs kategória. Hozd létre az elsőt a fenti űrlapon.
            </div>
        @endforelse
    </div>
</main>
</body>
</html>
