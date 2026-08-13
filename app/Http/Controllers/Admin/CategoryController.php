<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Kategóriák listázása a Hostadminon
     */
    public function index()
    {
        $this->authorizeHostadmin();

        $categories = Category::query()
            ->withCount(['quizzes', 'questions'])
            ->latest()
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Új kategória mentése
     */
    public function store(Request $request)
    {
        $this->authorizeHostadmin();

        // Validáljuk a kétnyelvű bemenetet
        $request->validate([
            'name.hu' => 'required|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'icon'    => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        Category::create([
            'name' => [
                'hu' => $request->input('name.hu'),
                'en' => $request->input('name.en'),
            ],
            // A slug-ot az angol vagy magyar névből képezzük automatikusan
            'slug' => $this->uniqueSlug($request->input('name.en') ?: $request->input('name.hu')),
            'icon' => $request->input('icon', 'fa-folder'),
            'description' => $request->input('description'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Kategória sikeresen létrehozva!');
    }

    /**
     * Kategória frissítése
     */
    public function update(Request $request, Category $category)
    {
        $this->authorizeHostadmin();

        $request->validate([
            'name.hu' => 'required|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'icon'    => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $category->update([
            'name' => [
                'hu' => $request->input('name.hu'),
                'en' => $request->input('name.en'),
            ],
            'slug' => $this->uniqueSlug(
                $request->input('name.en') ?: $request->input('name.hu'),
                $category
            ),
            'icon' => $request->input('icon'),
            'description' => $request->input('description'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Kategória sikeresen frissítve!');
    }

    /**
     * Kategória törlése
     */
    public function destroy(Category $category)
    {
        $this->authorizeHostadmin();

        // Az adatbázis kaszkádos idegen kulcsai miatt egy használt kategória
        // törlése kvízeket és kérdéseket is eltávolítana. Ezt itt védjük ki.
        if ($category->quizzes()->exists() || $category->questions()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Használatban lévő kategória nem törölhető. Inkább állítsd inaktívra.',
            ]);
        }

        $category->delete();
        return redirect()->back()->with('success', 'Kategória törölve!');
    }

    /**
     * A kategóriakezelés kizárólag a host admin feladata.
     */
    private function authorizeHostadmin(): void
    {
        abort_unless(auth()->user()?->isHostadmin(), 403);
    }

    /**
     * Emberileg olvasható, de adatbázis-szinten is egyedi slugot készít.
     */
    private function uniqueSlug(string $name, ?Category $ignoredCategory = null): string
    {
        $baseSlug = Str::slug($name) ?: 'kategoria';
        $slug = $baseSlug;
        $suffix = 2;

        while (Category::query()
            ->when($ignoredCategory, fn ($query) => $query->whereKeyNot($ignoredCategory->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
