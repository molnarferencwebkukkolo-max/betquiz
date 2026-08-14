<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionDatabaseBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migration_contains_only_required_system_content(): void
    {
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('categories', 20);
        $this->assertDatabaseCount('contents', 3);
        $this->assertDatabaseCount('ad_placements', 4);
        $this->assertDatabaseCount('advertisements', 0);

        $this->assertDatabaseMissing('users', ['email' => 'admin@test.com']);
        $this->assertDatabaseMissing('quizzes', ['title' => '🎮 DEMÓ Kvíz (Általános kérdések)']);
        $this->assertFalse(DB::table('categories')->where('slug', 'altalanos')->exists());
    }

    public function test_default_seeder_does_not_add_demo_or_login_data(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('categories', 20);
        $this->assertDatabaseCount('contents', 3);
        $this->assertDatabaseCount('ad_placements', 4);
    }
}
