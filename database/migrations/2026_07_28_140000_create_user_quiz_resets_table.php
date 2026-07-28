<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_quiz_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('reset_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quiz_resets');
    }
};
