<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Kategóriák listázása a Hostadminon
     */
    public function index()
    {
        $categories = Category::withCount('questions')->latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Új kategória mentése
     */
    public function store(Request $request)
    {
        // Validáljuk a kétnyelvű bemenetet
        $request->validate([
            'name.hu' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'icon'    => 'nullable|string|max:50',
        ]);

        Category::create([
            'name' => [
                'hu' => $request->input('name.hu'),
                'en' => $request->input('name.en'),
            ],
            // A slug-ot az angol vagy magyar névből képezzük automatikusan
            'slug' => Str::slug($request->input('name.en') ?: $request->input('name.hu')),
            'icon' => $request->input('icon', 'fa-folder'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Kategória sikeresen létrehozva!');
    }

    /**
     * Kategória frissítése
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name.hu' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'icon'    => 'nullable|string|max:50',
        ]);

        $category->update([
            'name' => [
                'hu' => $request->input('name.hu'),
                'en' => $request->input('name.en'),
            ],
            'slug' => Str::slug($request->input('name.en') ?: $request->input('name.hu')),
            'icon' => $request->input('icon'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Kategória sikeresen frissítve!');
    }

    /**
     * Kategória törlése
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->back()->with('success', 'Kategória törölve!');
    }
}
