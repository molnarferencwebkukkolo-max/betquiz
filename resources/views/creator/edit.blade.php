<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kvíz szerkesztése - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('my-quizzes.show', $quiz) }}" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
            ← Vissza a kvízhez
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow-md border border-gray-100 p-8">
        <h1 class="text-2xl font-extrabold text-gray-800 mb-6">Kvíz alapadatainak szerkesztése</h1>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold space-y-1">
                @foreach($errors->all() as $err)
                    <p>• {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('my-quizzes.update', $quiz) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Kvíz címe *</label>
                <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required
                       class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-bold text-gray-800">
            </div>

            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Kategória *</label>
                <select name="category_id" required class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-semibold text-gray-800">
                    @foreach($categories as $cat)
                        @php
                            $cName = is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name;
                        @endphp
                        <option value="{{ $cat->id }}" {{ $quiz->category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Leírás</label>
                <textarea name="description" rows="4"
                          class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium text-gray-700"
                          placeholder="Rövid tájékoztató a kvíz témájáról...">{{ old('description', $quiz->description) }}</textarea>
            </div>

            @if(auth()->user()->isUseradmin() || auth()->user()->isHostadmin())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 space-y-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-amber-900 uppercase tracking-wide">SEO adatok és tagek</h2>
                        <p class="text-xs font-semibold text-amber-700 mt-1">Ezeket csak admin szerkesztheti. Üres SEO mező esetén a rendszer a címet és a leírás első 160 karakterét használja.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-gray-700 mb-2">SEO title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $quiz->seo_title ?? $quiz->title) }}" maxlength="255"
                               class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-bold text-gray-800">
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-gray-700 mb-2">SEO description</label>
                        <textarea name="seo_description" rows="3" maxlength="160"
                                  class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium text-gray-700">{{ old('seo_description', $quiz->seo_description ?? $quiz->effective_seo_description) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Maximum 160 karakter.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-gray-700 mb-2">Tagek</label>
                        <input type="hidden" name="tags" id="tags-value" value="{{ old('tags', $quiz->tags->pluck('name')->implode(', ')) }}">
                        <div id="tag-chips" class="flex flex-wrap gap-2 mb-2"></div>
                        <input type="text" id="tag-input" list="tag-suggestions" placeholder="Kezdj el gépelni, majd Enter..."
                               class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium text-gray-700">
                        <datalist id="tag-suggestions">
                            @foreach($allTags as $tagName)
                                <option value="{{ $tagName }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Fejléckép / Borítókép</label>

                @if($quiz->cover_image)
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 mb-1 font-bold">Jelenlegi kép:</p>
                        <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="Borítókép" class="h-40 w-full object-cover rounded-2xl border">
                    </div>
                @endif

                <input type="file" name="cover_image" accept="image/*"
                       class="w-full text-sm bg-gray-50 p-3 rounded-2xl border border-gray-200 font-medium text-gray-600">
                <p class="text-xs text-gray-400 mt-1">Megengedett formátumok: JPG, PNG, WEBP (max. 5MB)</p>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('my-quizzes.show', $quiz) }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition">
                    Mégse
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl shadow-lg transition">
                    Módosítások mentése
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const tagInput = document.getElementById('tag-input');
    const tagsValue = document.getElementById('tags-value');
    const tagChips = document.getElementById('tag-chips');

    function getTags() {
        if (!tagsValue) return [];

        return tagsValue.value
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean);
    }

    function setTags(tags) {
        tagsValue.value = tags.join(', ');
        renderTags();
    }

    function addPendingTag() {
        const tag = tagInput.value.trim();
        if (!tag) return;

        const tags = getTags();
        if (!tags.some((currentTag) => currentTag.toLowerCase() === tag.toLowerCase())) {
            tags.push(tag);
            setTags(tags);
        }

        tagInput.value = '';
    }

    function renderTags() {
        if (!tagChips) return;

        tagChips.innerHTML = '';
        getTags().forEach((tag) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-extrabold';
            chip.textContent = tag + ' x';
            chip.addEventListener('click', () => {
                setTags(getTags().filter((currentTag) => currentTag.toLowerCase() !== tag.toLowerCase()));
            });
            tagChips.appendChild(chip);
        });
    }

    if (tagInput && tagsValue) {
        renderTags();

        tagInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ',') return;

            event.preventDefault();
            addPendingTag();
        });

        tagInput.closest('form')?.addEventListener('submit', () => {
            addPendingTag();
        });
    }
</script>

</body>
</html>
