<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Kedvencek tábla (ha még nem létezne)
        if (!Schema::hasTable('quiz_user_favorites')) {
            Schema::create('quiz_user_favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_id', 'quiz_id']);
            });
        }

        // 2. Dislike (Nem tetszik) tábla
        if (!Schema::hasTable('quiz_user_dislikes')) {
            Schema::create('quiz_user_dislikes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_id', 'quiz_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_user_dislikes');
        Schema::dropIfExists('quiz_user_favorites');
    }
};
