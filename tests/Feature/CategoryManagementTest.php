<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hostadmin_can_create_and_update_a_category(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($hostadmin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Kategóriák kezelése');

        $this->actingAs($hostadmin)->post(route('admin.categories.store'), [
            'name' => ['hu' => 'Történelem', 'en' => 'History'],
            'icon' => 'fa-landmark',
            'is_active' => '1',
        ])->assertRedirect();

        $category = Category::where('slug', 'history')->firstOrFail();
        $this->assertSame('history', $category->slug);

        // Azonos angol név mellett sem futhatunk egyedi adatbázis-slug hibába.
        $this->actingAs($hostadmin)->post(route('admin.categories.store'), [
            'name' => ['hu' => 'Másik történelem', 'en' => 'History'],
            'is_active' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'history-2']);

        $this->actingAs($hostadmin)->put(route('admin.categories.update', $category), [
            'name' => ['hu' => 'Világtörténelem', 'en' => 'World History'],
            'icon' => 'fa-globe',
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'slug' => 'world-history',
            'icon' => 'fa-globe',
            'is_active' => false,
        ]);
    }

    public function test_non_hostadmin_cannot_manage_categories(): void
    {
        $useradmin = User::factory()->create(['role' => 'useradmin']);

        $this->actingAs($useradmin)
            ->get(route('admin.categories.index'))
            ->assertForbidden();

        $this->actingAs($useradmin)->post(route('admin.categories.store'), [
            'name' => ['hu' => 'Tiltott'],
        ])->assertForbidden();
    }

    public function test_category_used_by_a_quiz_cannot_be_deleted(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $category = Category::create([
            'name' => ['hu' => 'Használt'],
            'slug' => 'hasznalt',
            'is_active' => true,
        ]);

        Quiz::create([
            'creator_id' => $hostadmin->id,
            'category_id' => $category->id,
            'title' => 'Kapcsolt kvíz',
            'description' => 'Törlési védelem tesztelése.',
            'status' => 'pending',
        ]);

        $this->actingAs($hostadmin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('quizzes', ['category_id' => $category->id]);
    }
}
