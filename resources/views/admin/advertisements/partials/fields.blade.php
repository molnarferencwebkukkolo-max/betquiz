@php($selectedPlacements = collect(old('placements', $advertisement?->placements->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all())
@php($selectedType = old('type', $advertisement?->type ?? 'image'))
@php($selectedCategories = collect(old('categories', $advertisement?->categories->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all())
@php($selectedQuizzes = collect(old('quizzes', $advertisement?->quizzes->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all())

<label class="block">
    <span class="ad-field-label">Belső név</span>
    <input type="text" name="name" required maxlength="150" value="{{ old('name', $advertisement?->name) }}" placeholder="Például: Nyári partnerkampány">
</label>
<label class="block">
    <span class="ad-field-label">Hirdetés típusa</span>
    <select name="type" required>
        <option value="image" @selected($selectedType === 'image')>Feltöltött kép + link</option>
        <option value="adsense" @selected($selectedType === 'adsense')>Google AdSense-kód</option>
    </select>
</label>

<div data-for-ad-type="image" class="space-y-5 lg:col-span-2">
    <label class="block">
        <span class="ad-field-label">Bannerkép {{ $advertisement?->image_path ? '(csak cserénél szükséges)' : '' }}</span>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <label class="block"><span class="ad-field-label">Cél URL</span><input type="url" name="target_url" value="{{ old('target_url', $advertisement?->target_url) }}" placeholder="https://..."></label>
        <label class="block"><span class="ad-field-label">Alternatív képszöveg</span><input type="text" name="alt_text" maxlength="255" value="{{ old('alt_text', $advertisement?->alt_text) }}"></label>
    </div>
</div>

<label data-for-ad-type="adsense" class="block lg:col-span-2">
    <span class="ad-field-label">Google AdSense-kód</span>
    <textarea name="adsense_code" rows="7" placeholder="Illeszd be a Google AdSense felületéről kapott teljes kódot…">{{ old('adsense_code', $advertisement?->adsense_code) }}</textarea>
    <small class="mt-2 block text-slate-500">Más szolgáltatótól származó vagy egyedi JavaScript biztonsági okból nem menthető.</small>
</label>

<fieldset class="rounded-xl border border-slate-700 p-4">
    <legend class="px-2 text-xs font-black uppercase tracking-wide text-slate-400">Megjelenési pozíciók</legend>
    <div class="space-y-3">
        @foreach($placements as $placement)
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="placements[]" value="{{ $placement->id }}" class="mt-1" @checked(in_array($placement->id, $selectedPlacements, true))>
                <span><strong class="block text-sm text-white">{{ $placement->name }}</strong><small class="text-slate-500">{{ $placement->description }}</small></span>
            </label>
        @endforeach
    </div>
</fieldset>

<fieldset class="rounded-xl border border-slate-700 p-4 lg:col-span-2">
    <legend class="px-2 text-xs font-black uppercase tracking-wide text-slate-400">Opcionális célzás</legend>
    <p class="mb-4 text-sm text-slate-400">Ha sem kategóriát, sem kvízt nem választasz, a hirdetés mindenhol megjelenhet. Kijelölés esetén kizárólag az egyező kategóriákban vagy konkrét kvízekben vesz részt a rotációban.</p>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <label class="block">
            <span class="ad-field-label">Kategóriák</span>
            <select name="categories[]" multiple size="8" class="min-h-48">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategories, true))>{{ $category->icon }} {{ $category->translated_name }}</option>
                @endforeach
            </select>
            <small class="mt-2 block text-slate-500">Több elemhez Ctrl/Cmd + kattintás használható.</small>
        </label>
        <label class="block">
            <span class="ad-field-label">Konkrét kvízek</span>
            <input type="search" data-ad-quiz-filter placeholder="Kvíz keresése…" class="mb-2">
            <select name="quizzes[]" multiple size="8" class="min-h-48" data-ad-quiz-select>
                @foreach($quizzes as $quizOption)
                    <option value="{{ $quizOption->id }}" @selected(in_array($quizOption->id, $selectedQuizzes, true))>{{ $quizOption->title }} — {{ $quizOption->category?->translated_name ?? 'Nincs kategória' }}</option>
                @endforeach
            </select>
            <small class="mt-2 block text-slate-500">A keresés nem törli a már kijelölt kvízeket.</small>
        </label>
    </div>
</fieldset>

<div class="grid grid-cols-2 gap-4">
    <label><span class="ad-field-label">Megjelenési súly</span><input type="number" name="weight" min="1" max="100" value="{{ old('weight', $advertisement?->weight ?? 1) }}" required></label>
    <label class="flex items-center gap-3 pt-7"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $advertisement?->is_active ?? true))><span class="font-bold text-slate-200">Aktív</span></label>
</div>
<label><span class="ad-field-label">Kezdés (opcionális)</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $advertisement?->starts_at?->format('Y-m-d\TH:i')) }}"></label>
<label><span class="ad-field-label">Lejárat (opcionális)</span><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $advertisement?->ends_at?->format('Y-m-d\TH:i')) }}"></label>
